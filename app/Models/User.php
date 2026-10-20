<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\Db;
use PDO;

final class User
{
    /** @return array{id:int,name:string,email:string,password_hash:string,role:string,is_active:int,failed_attempts:int,locked_until:?string}|null */
    public static function findByEmail(string $email): ?array
    {
        $stmt = Db::pdo()->prepare(
            'SELECT id, name, email, password_hash, role, is_active, failed_attempts, locked_until
               FROM users
              WHERE email = :email
              LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Db::pdo()->prepare(
            'SELECT id, name, email, role, is_active, last_login_at
               FROM users
              WHERE id = :id
              LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function recordSuccessfulLogin(int $id): void
    {
        $stmt = Db::pdo()->prepare(
            'UPDATE users
                SET failed_attempts = 0,
                    locked_until = NULL,
                    last_login_at = UTC_TIMESTAMP()
              WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }

    /**
     * Atomically increment failed attempts and set lockout when threshold hit.
     * Returns the new failed_attempts count.
     */
    public static function recordFailedLogin(int $id, int $maxAttempts, int $lockMinutes): int
    {
        $stmt = Db::pdo()->prepare(
            'UPDATE users
                SET failed_attempts = failed_attempts + 1,
                    locked_until = CASE
                        WHEN failed_attempts + 1 >= :max THEN (UTC_TIMESTAMP() + INTERVAL :mins MINUTE)
                        ELSE locked_until
                    END
              WHERE id = :id'
        );
        $stmt->bindValue(':max', $maxAttempts, PDO::PARAM_INT);
        $stmt->bindValue(':mins', $lockMinutes, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = Db::pdo()->prepare('SELECT failed_attempts FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public static function isLocked(array $user): bool
    {
        if (empty($user['locked_until'])) {
            return false;
        }
        return strtotime($user['locked_until'] . ' UTC') > time();
    }
}