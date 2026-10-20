<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * CSRF token helper.
 * - One token per form name, generated lazily.
 * - Tokens are 32 random bytes hex-encoded (64 hex chars).
 * - Sessions are started once, with hardened cookie flags set.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf';

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? null) === '443';

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // Use strict mode and disable uninitialized IDs.
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_start();
    }

    public static function token(string $name): string
    {
        self::startSession();
        if (!isset($_SESSION[self::SESSION_KEY][$name]) ||
            !is_string($_SESSION[self::SESSION_KEY][$name])) {
            $_SESSION[self::SESSION_KEY][$name] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY][$name];
    }

    public static function verify(string $name, ?string $submitted): bool
    {
        self::startSession();
        $expected = $_SESSION[self::SESSION_KEY][$name] ?? null;
        if (!is_string($expected) || $expected === '') {
            return false;
        }
        if (!is_string($submitted) || $submitted === '') {
            return false;
        }
        return hash_equals($expected, $submitted);
    }

    /** Rotate after successful submission to prevent replay. */
    public static function rotate(string $name): void
    {
        self::startSession();
        unset($_SESSION[self::SESSION_KEY][$name]);
    }
}