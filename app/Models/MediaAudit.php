<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\Db;
use PDO;

final class MediaAudit
{
    /**
     * Record a media operation. Never throws — audit failures must not break uploads.
     */
    public static function log(
        ?int $userId,
        ?int $projectId,
        string $action,
        string $kind,
        string $storedPath,
        ?string $originalName = null,
        ?string $declaredMime = null,
        ?string $detectedMime = null,
        int $sizeBytes = 0,
        ?int $width = null,
        ?int $height = null,
        ?string $sha256 = null,
        string $result = 'ok',
        ?string $reason = null
    ): void {
        try {
            $stmt = Db::pdo()->prepare(
                'INSERT INTO media_audit
                    (user_id, project_id, action, media_kind, stored_path, original_name,
                     declared_mime, detected_mime, size_bytes, width, height, sha256, result, reason)
                 VALUES
                    (:u, :p, :a, :k, :sp, :on, :dm, :det, :sz, :w, :h, :sha, :res, :r)'
            );
            $stmt->bindValue(':u',   $userId,   $userId  === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':p',   $projectId,$projectId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':a',   $action,   PDO::PARAM_STR);
            $stmt->bindValue(':k',   $kind,     PDO::PARAM_STR);
            $stmt->bindValue(':sp',  $storedPath, PDO::PARAM_STR);
            $stmt->bindValue(':on',  $originalName, $originalName === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':dm',  $declaredMime, $declaredMime === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':det', $detectedMime, $detectedMime === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':sz',  $sizeBytes, PDO::PARAM_INT);
            $stmt->bindValue(':w',   $width,  $width  === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':h',   $height, $height === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':sha', $sha256, $sha256 === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':res', $result, PDO::PARAM_STR);
            $stmt->bindValue(':r',   $reason, $reason === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->execute();
        } catch (\Throwable $e) {
            error_log('[media audit] ' . $e->getMessage());
        }
    }
}