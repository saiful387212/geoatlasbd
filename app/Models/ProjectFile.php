<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\Db;
use PDO;

final class ProjectFile
{
    public static function add(
        int $projectId,
        string $originalName,
        string $filePath,
        string $mimeType = 'application/pdf',
        int $sizeBytes = 0
    ): void {
        $stmt = Db::pdo()->prepare(
            'INSERT INTO project_files (project_id, original_name, file_path, mime_type, size_bytes)
             VALUES (:project_id, :original_name, :file_path, :mime_type, :size_bytes)'
        );
        $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->bindValue(':original_name', $originalName, PDO::PARAM_STR);
        $stmt->bindValue(':file_path', $filePath, PDO::PARAM_STR);
        $stmt->bindValue(':mime_type', $mimeType, PDO::PARAM_STR);
        $stmt->bindValue(':size_bytes', $sizeBytes, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function delete(int $fileId): ?string
    {
        $stmt = Db::pdo()->prepare('SELECT file_path FROM project_files WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $fileId]);
        $path = $stmt->fetchColumn();
        if ($path === false) {
            return null;
        }

        $stmt = Db::pdo()->prepare('DELETE FROM project_files WHERE id = :id');
        $stmt->execute([':id' => $fileId]);
        return (string) $path;
    }

    /** @return array<int,array{id:int,original_name:string,file_path:string,mime_type:string,size_bytes:int}> */
    public static function forProject(int $projectId): array
    {
        $stmt = Db::pdo()->prepare(
            'SELECT id, original_name, file_path, mime_type, size_bytes
               FROM project_files
              WHERE project_id = :project_id
              ORDER BY id ASC'
        );
        $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
