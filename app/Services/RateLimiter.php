<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * DB-backed per-IP rate limiter.
 * Uses contact_messages itself as the ledger — a submission is one attempt.
 * For higher-traffic deployments, replace with a `rate_limits` table.
 */
final class RateLimiter
{
    public static function ipHash(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        // Salt with APP_KEY so hashes can't be reversed by guessing.
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        return hash('sha256', $ip . '|' . $config['app']['key']);
    }

    /** Count submissions from this IP hash in the last $minutes. */
    public static function recentSubmissions(int $minutes = 60): int
    {
        $sql = "SELECT COUNT(*) FROM contact_messages
                 WHERE ip_hash = :h
                   AND created_at >= (UTC_TIMESTAMP() - INTERVAL :m MINUTE)";
        $stmt = Db::pdo()->prepare($sql);
        $stmt->bindValue(':h', self::ipHash(), PDO::PARAM_STR);
        $stmt->bindValue(':m', max(1, $minutes), PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /** True if the IP has hit the limit. */
    public static function limited(int $maxPerHour = 5): bool
    {
        return self::recentSubmissions(60) >= $maxPerHour;
    }
}