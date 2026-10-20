<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Single PDO instance, initialized on first use.
 *
 * Design notes:
 *  - ERRMODE_EXCEPTION: failures throw, never silently return false.
 *  - EMULATE_PREPARES = false: real server-side prepared statements.
 *  - FETCH_ASSOC default: arrays, not objects.
 *  - utf8mb4 enforced at DSN and via SET NAMES fallback.
 *  - Credentials supplied from config, never hardcoded here.
 */
final class Db
{
    private static ?PDO $pdo = null;

    /**
     * @param array{host:string,port:int,name:string,user:string,pass:string,charset:string} $cfg
     */
    public static function init(array $cfg): void
    {
        if (self::$pdo instanceof PDO) {
            return;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
            $cfg['name'],
            $cfg['charset']
        );

        try {
            self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
                PDO::ATTR_PERSISTENT         => false,
            ]);
            // Belt-and-suspenders for older MySQL clients:
            self::$pdo->exec("SET NAMES {$cfg['charset']} COLLATE {$cfg['charset']}_unicode_ci");
            self::$pdo->exec("SET time_zone = '+00:00'");
        } catch (PDOException $e) {
            // Do NOT leak credentials or DSN into the message sent to users.
            error_log('[DB] Connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed. Check configuration.', 0, $e);
        }
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo instanceof PDO) {
            $config = require dirname(__DIR__, 2) . '/config/config.php';
            self::init($config['db']);
        }
        return self::$pdo;
    }

    /** Test-only: reset the singleton (used by verify_connection.php). */
    public static function reset(): void
    {
        self::$pdo = null;
    }
}