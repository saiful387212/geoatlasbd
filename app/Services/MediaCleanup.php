<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Orphan detection and cleanup.
 * Invoked from CLI (php app/Services/MediaCleanup.php) or scheduled.
 *
 * Two reports:
 *   - Files on disk with no DB row → candidates for deletion
 *   - DB rows whose file is missing → broken links (log only; never auto-delete)
 */
final class MediaCleanup
{
    /**
     * @return array{orphan_files:string[], missing_files:string[]}
     */
    public static function scan(string $imagesDir, string $pdfsDir): array
    {
        $pdo = Db::pdo();

        $dbImages = array_map(
            fn($r) => basename((string) $r['file_path']),
            $pdo->query('SELECT file_path FROM project_images')->fetchAll()
        );
        $dbCovers = array_map(
            fn($r) => basename((string) $r['file_path']),
            $pdo->query("SELECT cover_image AS file_path FROM projects WHERE cover_image IS NOT NULL")->fetchAll()
        );
        $dbPdfs = array_map(
            fn($r) => basename((string) $r['file_path']),
            $pdo->query('SELECT file_path FROM project_files')->fetchAll()
        );

        $dbImagesAll = array_unique(array_merge($dbImages, $dbCovers));

        $orphanImages = self::orphansInDir($imagesDir, $dbImagesAll);
        $orphanPdfs   = self::orphansInDir($pdfsDir, $dbPdfs);

        $missingImages = array_values(array_filter($dbImagesAll, fn($n) => $n !== '' && !is_file($imagesDir . DIRECTORY_SEPARATOR . $n)));
        $missingPdfs   = array_values(array_filter($dbPdfs,     fn($n) => $n !== '' && !is_file($pdfsDir   . DIRECTORY_SEPARATOR . $n)));

        return [
            'orphan_files'  => array_merge(
                array_map(fn($n) => 'images/' . $n, $orphanImages),
                array_map(fn($n) => 'pdfs/' . $n,   $orphanPdfs)
            ),
            'missing_files' => array_merge($missingImages, $missingPdfs),
        ];
    }

    /** @param string[] $knownDbNames */
    private static function orphansInDir(string $dir, array $knownDbNames): array
    {
        if (!is_dir($dir)) return [];
        $out = [];
        $knownDbNames = array_flip($knownDbNames);
        foreach (scandir($dir) ?: [] as $n) {
            if ($n === '.' || $n === '..' || $n === '.htaccess' || $n === '.gitkeep') continue;
            if (!is_file($dir . DIRECTORY_SEPARATOR . $n)) continue;
            if (!isset($knownDbNames[$n])) $out[] = $n;
        }
        return $out;
    }
}

// ---- CLI entrypoint ----
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    require dirname(__DIR__, 2) . '/app/bootstrap.php';
    $imagesDir = dirname(__DIR__, 2) . '/public/uploads/images';
    $pdfsDir   = dirname(__DIR__, 2) . '/public/uploads/pdfs';

    $report = MediaCleanup::scan($imagesDir, $pdfsDir);
    echo "=== Media cleanup scan ===\n";
    echo "Orphan files (on disk, no DB row): " . count($report['orphan_files']) . "\n";
    foreach ($report['orphan_files'] as $f) echo "  - {$f}\n";
    echo "Missing files (DB row, no file):   " . count($report['missing_files']) . "\n";
    foreach ($report['missing_files'] as $f) echo "  ! {$f}\n";

    if (($argv[1] ?? '') === '--delete') {
        echo "Deleting orphans...\n";
        $base = dirname(__DIR__, 2) . '/public/uploads';
        foreach ($report['orphan_files'] as $rel) {
            if (Uploader::deleteSafe($base, $rel)) {
                echo "  deleted {$rel}\n";
            } else {
                echo "  FAILED {$rel}\n";
            }
        }
    }
    exit(0);
}