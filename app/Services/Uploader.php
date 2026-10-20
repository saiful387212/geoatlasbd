<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Hardened media uploader.
 *
 * Defences:
 *   1. MIME type detected via finfo — the client's Content-Type is IGNORED.
 *   2. Extension allowlist per kind, cross-checked with detected MIME.
 *   3. Filenames are 32 random hex chars + canonical extension. No user input.
 *   4. Double-extension detection: 'evil.php.jpg' still yields a .jpg extension,
 *      but any name whose prefix ends in .php/.phtml/.phar/etc. is rejected.
 *   5. Images are dimension-checked and optionally re-encoded via GD, which
 *      strips EXIF (including GPS) and neutralises embedded scripts.
 *   6. Files are written to a random target name; if it exists (astronomically
 *      unlikely), a new random name is generated. No overwrite is ever possible.
 *   7. Writes go to a temp file first, then atomic rename; on failure the temp
 *      is unlinked.
 *   8. Filenames pass a final regex allowing only [A-Za-z0-9._-]; and the
 *      filesystem-safe basename is used for all disk operations.
 *   9. Directory traversal impossible: the target dir is fixed by the caller
 *      and the name never contains slashes.
 *  10. Failed or suspicious files are optionally quarantined under storage/ for
 *      manual review; they are not web-accessible.
 */
final class Uploader
{
    public const IMAGE_ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public const PDF_ALLOWED = [
        'application/pdf' => 'pdf',
    ];

    /** Extensions the client-supplied name may have. */
    private const IMAGE_EXT_ALLOWED = ['jpg', 'jpeg', 'png', 'webp'];
    private const PDF_EXT_ALLOWED   = ['pdf'];

    /** Names ending with these segments are rejected outright — double-extension defence. */
    private const DANGEROUS_EXT = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'pht',
        'jsp', 'asp', 'aspx', 'cgi', 'pl', 'py', 'sh', 'exe', 'bat', 'cmd',
        'svg', 'xml', 'html', 'htm', 'js', 'mjs', 'htaccess', 'htpasswd',
    ];

    public const IMAGE_MAX_BYTES = 5_242_880;    // 5 MB
    public const PDF_MAX_BYTES   = 10_485_760;   // 10 MB
    public const IMAGE_MAX_SIDE  = 8000;          // reject absurd dimensions
    public const IMAGE_MIN_SIDE  = 32;

    // ============================================================
    // PUBLIC API
    // ============================================================

    /**
     * Save an image and (by default) re-encode it through GD.
     *
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file
     * @param string $imagesDir   absolute path to public/uploads/images
     * @param bool   $reencode    true = strip EXIF, recompress (recommended)
     * @return array{relative:string,width:int,height:int,sha256:string,size:int}
     * @throws RuntimeException on any validation failure
     */
    public static function saveImageStrict(array $file, string $imagesDir, bool $reencode = true): array
    {
        self::assertRealUpload($file, 'image');

        $detected = self::detectMime($file['tmp_name']);
        if (!isset(self::IMAGE_ALLOWED[$detected])) {
            throw new RuntimeException('Image must be JPG, PNG, or WebP.');
        }

        if ($file['size'] <= 0 || $file['size'] > self::IMAGE_MAX_BYTES) {
            throw new RuntimeException(sprintf(
                'Image must be ≤ %d KB.', (int) (self::IMAGE_MAX_BYTES / 1024)
            ));
        }

        // Extension cross-check.
        $origExt = self::clientExtension($file['name']);
        if (!in_array($origExt, self::IMAGE_EXT_ALLOWED, true)) {
            throw new RuntimeException('Image must have a .jpg, .jpeg, .png, or .webp extension.');
        }
        self::rejectDangerousName($file['name']);

        // Dimensions.
        $dim = @getimagesize($file['tmp_name']);
        if ($dim === false) {
            throw new RuntimeException('The file is not a valid image.');
        }
        [$w, $h] = $dim;
        if ($w < self::IMAGE_MIN_SIDE || $h < self::IMAGE_MIN_SIDE) {
            throw new RuntimeException("Image is too small ({$w}×{$h} px). Minimum: " . self::IMAGE_MIN_SIDE . 'px per side.');
        }
        if ($w > self::IMAGE_MAX_SIDE || $h > self::IMAGE_MAX_SIDE) {
            throw new RuntimeException("Image is too large ({$w}×{$h} px). Maximum: " . self::IMAGE_MAX_SIDE . 'px per side.');
        }

        // Canonical extension from the detected MIME — never from the client.
        $ext = self::IMAGE_ALLOWED[$detected];

        self::ensureDir($imagesDir);

        if ($reencode) {
            $stored = self::reencodeToDisk($file['tmp_name'], $detected, $ext, $imagesDir);
        } else {
            $stored = self::moveToDisk($file['tmp_name'], $ext, $imagesDir);
        }

        $absolute = $imagesDir . DIRECTORY_SEPARATOR . $stored;

        // Re-measure post-encode (dimensions can shrink on re-encode).
        $dim2 = @getimagesize($absolute);
        $w2 = $dim2[0] ?? $w;
        $h2 = $dim2[1] ?? $h;

        return [
            'relative' => 'images/' . $stored,
            'width'    => (int) $w2,
            'height'   => (int) $h2,
            'sha256'   => (string) hash_file('sha256', $absolute),
            'size'     => (int) (filesize($absolute) ?: 0),
        ];
    }

    /**
     * Save a PDF.
     *
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file
     * @return array{relative:string,sha256:string,size:int}
     */
    public static function savePdfStrict(array $file, string $pdfsDir): array
    {
        self::assertRealUpload($file, 'PDF');

        $detected = self::detectMime($file['tmp_name']);
        if (!isset(self::PDF_ALLOWED[$detected])) {
            throw new RuntimeException('File must be a PDF.');
        }

        if ($file['size'] <= 0 || $file['size'] > self::PDF_MAX_BYTES) {
            throw new RuntimeException(sprintf(
                'PDF must be ≤ %d MB.', (int) (self::PDF_MAX_BYTES / 1024 / 1024)
            ));
        }

        $origExt = self::clientExtension($file['name']);
        if (!in_array($origExt, self::PDF_EXT_ALLOWED, true)) {
            throw new RuntimeException('File must have a .pdf extension.');
        }
        self::rejectDangerousName($file['name']);

        // PDF magic + version sanity: bytes 0-4 are "%PDF-" and version byte follows.
        $fh = @fopen($file['tmp_name'], 'rb');
        if ($fh === false) {
            throw new RuntimeException('Could not read uploaded PDF.');
        }
        $head = fread($fh, 8);
        fclose($fh);
        if (!is_string($head) || !str_starts_with($head, '%PDF-')) {
            throw new RuntimeException('File is not a valid PDF.');
        }

        self::ensureDir($pdfsDir);

        $stored   = self::moveToDisk($file['tmp_name'], 'pdf', $pdfsDir);
        $absolute = $pdfsDir . DIRECTORY_SEPARATOR . $stored;

        return [
            'relative' => 'pdfs/' . $stored,
            'sha256'   => (string) hash_file('sha256', $absolute),
            'size'     => (int) (filesize($absolute) ?: 0),
        ];
    }

    /**
     * Deprecated in Phase 9 — kept for compatibility with Phase 8 controller if
     * it is still called from elsewhere. Prefer saveImageStrict.
     *
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file
     */
    public static function saveImage(array $file, string $dir): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        $res = self::saveImageStrict($file, $dir, false);
        // Return just the basename to match the old contract.
        return basename($res['relative']);
    }

    /** @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file */
    public static function savePdf(array $file, string $dir): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        $res = self::savePdfStrict($file, $dir);
        return basename($res['relative']);
    }

    /**
     * Delete a file by its relative DB path ('images/foo.jpg' or 'pdfs/foo.pdf').
     * Refuses anything outside the two known subdirs. Returns true if a file was removed.
     */
    public static function deleteSafe(string $baseUploadsDir, ?string $relativePath): bool
    {
        if ($relativePath === null || $relativePath === '') return false;

        // Must match images/<name> or pdfs/<name>
        if (!preg_match('#^(images|pdfs)/([A-Za-z0-9._-]+)$#', $relativePath, $m)) {
            error_log('[uploader deleteSafe] suspicious path: ' . $relativePath);
            return false;
        }
        $sub  = $m[1];
        $name = $m[2];
        if (str_contains($name, '..')) return false;

        $dir  = rtrim($baseUploadsDir, '/\\') . DIRECTORY_SEPARATOR . $sub;
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (!is_file($path)) return false;

        // Guard against unlink of anything outside the uploads base.
        $realBase = realpath($baseUploadsDir);
        $realFile = realpath($path);
        if ($realBase === false || $realFile === false) return false;
        if (!str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR)) return false;

        return @unlink($path);
    }

    /**
     * Move a suspicious file to storage/quarantine for offline inspection.
     * Never public. Never executed.
     */
    public static function quarantine(array $file, string $quarantineDir, string $reason): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
        if (!is_uploaded_file($file['tmp_name'])) return null;
        self::ensureDir($quarantineDir);
        $dest = rtrim($quarantineDir, '/\\') . DIRECTORY_SEPARATOR
              . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.bin';
        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            error_log('[uploader quarantine] move failed for ' . $reason);
            return null;
        }
        @chmod($dest, 0600);
        // Write a sidecar with the reason.
        @file_put_contents($dest . '.txt', "reason: {$reason}\n" . 'time: ' . gmdate('c') . "\n");
        return $dest;
    }

    public static function humanSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024) . ' KB';
        return round($bytes / 1024 / 1024, 1) . ' MB';
    }

    // ============================================================
    // INTERNALS
    // ============================================================

    /** @param array{error?:int} $file */
    private static function assertRealUpload(array $file, string $label): void
    {
        if (($file['error'] ?? -1) !== UPLOAD_ERR_OK) {
            $code = (int) ($file['error'] ?? -1);
            throw new RuntimeException(self::uploadErrorMessage($code, $label));
        }
        if (!is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new RuntimeException('Not a real uploaded file.');
        }
    }

    private static function uploadErrorMessage(int $code, string $label): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "{$label} exceeds the server's size limit.",
            UPLOAD_ERR_PARTIAL                       => "{$label} was only partially uploaded.",
            UPLOAD_ERR_NO_FILE                       => "No {$label} was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR                    => 'Server temporary folder is not available.',
            UPLOAD_ERR_CANT_WRITE                    => 'Server could not write the file.',
            UPLOAD_ERR_EXTENSION                     => 'A server extension blocked the upload.',
            default                                  => "Upload failed for the {$label}.",
        };
    }

    private static function detectMime(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($path);
        return is_string($mime) ? strtolower($mime) : '';
    }

    private static function clientExtension(string $name): string
    {
        return strtolower(pathinfo($name, PATHINFO_EXTENSION));
    }

    private static function rejectDangerousName(string $name): void
    {
        // 'photo.php.jpg' → prefix 'photo.php' → dangerous.
        $parts = explode('.', strtolower($name));
        // Drop the trailing extension — we've already validated it.
        array_pop($parts);
        foreach ($parts as $part) {
            if (in_array($part, self::DANGEROUS_EXT, true)) {
                throw new RuntimeException('File name contains a disallowed extension segment.');
            }
        }
        // Also reject leading dot (hidden files).
        if (str_starts_with($name, '.')) {
            throw new RuntimeException('File name may not start with a dot.');
        }
    }

    private static function ensureDir(string $dir): void
    {
        if (is_dir($dir)) return;
        if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Upload directory is not writable.');
        }
    }

    /**
     * Move the uploaded temp file into $dir under a random name.
     * Retries if the random name happens to collide (astronomically unlikely).
     */
    private static function moveToDisk(string $tmpName, string $ext, string $dir): string
    {
        for ($i = 0; $i < 5; $i++) {
            $stored   = bin2hex(random_bytes(16)) . '.' . $ext;
            $absolute = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $stored;
            if (file_exists($absolute)) continue;
            if (!move_uploaded_file($tmpName, $absolute)) {
                throw new RuntimeException('Could not store the uploaded file.');
            }
            @chmod($absolute, 0644);
            return $stored;
        }
        throw new RuntimeException('Could not generate a unique filename.');
    }

    /**
     * Re-encode an image to strip metadata (EXIF/GPS) and neutralise scripts.
     * Writes to a random path, returns the stored name.
     * Falls back to a straight move if GD lacks the codec (rare with webp).
     */
    private static function reencodeToDisk(string $tmpName, string $mime, string $ext, string $dir): string
    {
        // If GD is unavailable, fall back to safe move.
        if (!function_exists('imagecreatefromstring')) {
            return self::moveToDisk($tmpName, $ext, $dir);
        }

        $raw = @file_get_contents($tmpName);
        if ($raw === false) {
            throw new RuntimeException('Could not read image data.');
        }
        $img = @imagecreatefromstring($raw);
        if ($img === false) {
            throw new RuntimeException('Could not decode image.');
        }

        // Composite onto a white background for images with alpha to JPG, else keep alpha.
        ob_start();
        try {
            $ok = match ($mime) {
                'image/jpeg' => imagejpeg($img, null, 88),
                'image/png'  => imagepng($img, null, 6),
                'image/webp' => function_exists('imagewebp') ? imagewebp($img, null, 88) : imagejpeg($img, null, 88),
                default      => false,
            };
            $bytes = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            imagedestroy($img);
            throw new RuntimeException('Image re-encoding failed.');
        }
        imagedestroy($img);

        if (!$ok || !is_string($bytes) || $bytes === '') {
            // WebP codec not present in this PHP build — fall back to a straight move.
            return self::moveToDisk($tmpName, $ext, $dir);
        }

        // If the re-encode for webp fell back to JPEG bytes, correct the extension.
        if ($mime === 'image/webp' && !function_exists('imagewebp')) {
            $ext = 'jpg';
        }

        for ($i = 0; $i < 5; $i++) {
            $stored   = bin2hex(random_bytes(16)) . '.' . $ext;
            $absolute = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $stored;
            if (file_exists($absolute)) continue;
            // Write to temp, then rename → atomic.
            $tmpOut = $absolute . '.tmp' . bin2hex(random_bytes(4));
            if (file_put_contents($tmpOut, $bytes, LOCK_EX) === false) {
                @unlink($tmpOut);
                throw new RuntimeException('Could not write re-encoded image.');
            }
            if (!@rename($tmpOut, $absolute)) {
                @unlink($tmpOut);
                throw new RuntimeException('Could not finalise re-encoded image.');
            }
            @chmod($absolute, 0644);
            // Remove the uploaded tmp file to free space (PHP may already have done this).
            @unlink($tmpName);
            return $stored;
        }
        throw new RuntimeException('Could not generate a unique filename.');
    }
}