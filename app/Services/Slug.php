<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Db;
use PDO;

/**
 * Slug generator.
 * - Lowercase, ASCII-only, hyphens between words.
 * - Handles collisions by appending -2, -3, ... up to -999.
 * - Never returns an empty slug: falls back to a random 8-char suffix.
 */
final class Slug
{
    public static function make(string $title, ?int $ignoreProjectId = null): string
    {
        $base = self::normalize($title);

        if ($base === '') {
            $base = 'project-' . bin2hex(random_bytes(4));
        }

        // Collision loop.
        for ($i = 0; $i <= 999; $i++) {
            $candidate = $i === 0 ? $base : $base . '-' . ($i + 1);
            if (!self::exists($candidate, $ignoreProjectId)) {
                return $candidate;
            }
        }

        return $base . '-' . bin2hex(random_bytes(4));
    }

    /** Validate a slug the admin explicitly typed. */
    public static function isValid(string $slug): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1
            && strlen($slug) <= 200;
    }

    /** Normalise an existing slug (e.g., from user input) — returns null if not usable. */
    public static function normalizeExisting(?string $raw): ?string
    {
        if ($raw === null) return null;
        $raw = strtolower(trim($raw));
        if ($raw === '' || strlen($raw) > 200) return null;
        $norm = self::normalize($raw);
        return $norm === '' ? null : $norm;
    }

    // ---- internals ----

    private static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');

        // Replace common accented/punctuated variants
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
        $text = preg_replace('/-+/', '-', $text) ?? '';
        $text = trim($text, '-');
        if (strlen($text) > 180) {
            $text = substr($text, 0, 180);
            $text = rtrim($text, '-');
        }
        return $text;
    }

    private static function exists(string $slug, ?int $ignoreId): bool
    {
        $sql = 'SELECT COUNT(*) FROM projects WHERE slug = :slug';
        $params = [':slug' => $slug];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignoreId;
        }
        $stmt = \App\Services\Db::pdo()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}