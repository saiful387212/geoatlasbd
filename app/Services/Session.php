<?php
declare(strict_types=1);

namespace App\Services;

final class Session
{
    private const KEY = '_auth';

    public static function setUser(int $userId, string $role, string $fingerprint): void
    {
        $_SESSION[self::KEY] = [
            'user_id'     => $userId,
            'role'        => $role,
            'fingerprint' => $fingerprint,
            'logged_in'   => time(),
            'last_seen'   => time(),
        ];
    }

    public static function user(): ?array
    {
        return $_SESSION[self::KEY] ?? null;
    }

    public static function clearUser(): void
    {
        unset($_SESSION[self::KEY]);
    }

    public static function touch(): void
    {
        if (isset($_SESSION[self::KEY])) {
            $_SESSION[self::KEY]['last_seen'] = time();
        }
    }
}