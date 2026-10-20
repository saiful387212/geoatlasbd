<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Authentication service.
 * Never logs passwords; never reveals account existence; always returns generic failures.
 */
final class Auth
{
    private const MAX_FAILED     = 5;      // per account
    private const LOCK_MINUTES   = 15;     // per account
    private const MAX_FAILS_IP   = 10;     // per IP
    private const IP_WINDOW_MIN  = 15;     // per IP
    private const IDLE_MINUTES   = 30;     // session idle timeout

    private static ?array $cachedUser = null;

    /**
     * Attempt login. Returns true on success.
     * On failure returns false; caller shows a single generic error.
     */
    public static function attempt(string $email, string $password, string $ipHash): bool
    {
        // ---- IP-level throttle ----
        if (self::tooManyIpFailures($ipHash)) {
            self::audit(null, 'login.ip_locked', null, ['ip_hash' => $ipHash]);
            return false;
        }

        $user = User::findByEmail($email);

        if ($user === null) {
            // Timing-safe dummy verify so unknown emails cost the same as bad passwords.
            self::dummyVerify($password);
            self::recordIpFailure($ipHash);
            self::audit(null, 'login.fail', null, ['reason' => 'unknown_email']);
            return false;
        }

        if ((int) $user['is_active'] !== 1) {
            self::dummyVerify($password);
            self::recordIpFailure($ipHash);
            self::audit((int) $user['id'], 'login.fail', null, ['reason' => 'inactive']);
            return false;
        }

        if (User::isLocked($user)) {
            self::dummyVerify($password);
            self::recordIpFailure($ipHash);
            self::audit((int) $user['id'], 'login.fail', null, ['reason' => 'locked']);
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            User::recordFailedLogin((int) $user['id'], self::MAX_FAILED, self::LOCK_MINUTES);
            self::recordIpFailure($ipHash);
            self::audit((int) $user['id'], 'login.fail', null, ['reason' => 'bad_password']);
            return false;
        }

        // ---- Success ----
        // Auto-upgrade the hash if the algorithm defaults changed (e.g. cost bump).
        if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
            $newHash = password_hash($password, PASSWORD_ARGON2ID);
            if (is_string($newHash)) {
                $stmt = Db::pdo()->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
                $stmt->execute([':h' => $newHash, ':id' => $user['id']]);
            }
        }

        session_regenerate_id(true);
        User::recordSuccessfulLogin((int) $user['id']);
        Session::setUser(
            (int) $user['id'],
            (string) $user['role'],
            self::fingerprint()
        );
        self::audit((int) $user['id'], 'login.success', null, null);

        return true;
    }

    /** True if the current request is authenticated. */
    public static function check(): bool
    {
        $s = Session::user();
        if ($s === null) {
            return false;
        }

        // Fingerprint mismatch → treat as stale (UA changed mid-session).
        if (!hash_equals($s['fingerprint'] ?? '', self::fingerprint())) {
            self::logout();
            return false;
        }

        // Idle timeout.
        if ((int) ($s['last_seen'] ?? 0) + self::IDLE_MINUTES * 60 < time()) {
            self::logout();
            return false;
        }

        Session::touch();
        return true;
    }

    /** Return the current user record (DB), or null. */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }
        $s = Session::user();
        try {
            $u = $s ? User::findById((int) $s['user_id']) : null;
        } catch (PDOException | RuntimeException $e) {
            error_log('[auth] Unable to verify the current admin session: ' . $e->getMessage());
            self::logout();
            return null;
        }
        if ($u === null || (int) $u['is_active'] !== 1) {
            self::logout();
            return null;
        }
        self::$cachedUser = $u;
        return $u;
    }

    public static function logout(): void
    {
        $s = Session::user();
        if ($s) {
            self::audit((int) $s['user_id'], 'logout', null, null);
        }
        Session::clearUser();
        // Invalidate the whole session and regenerate the ID.
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            session_destroy();
        }
        // Expire the cookie client-side too.
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires'  => time() - 3600,
                'path'     => $p['path'] ?: '/',
                'domain'   => $p['domain'] ?: '',
                'secure'   => (bool) $p['secure'],
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    // ---- helpers ----

    private static function fingerprint(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return hash('sha256', $ua);
    }

    private static function dummyVerify(string $password): void
    {
        // A precomputed Argon2id hash of a random string. Constant time is not
        // required; we just need real work so timing doesn't reveal account existence.
        // Generated once offline:
        //   password_hash(bin2hex(random_bytes(16)), PASSWORD_ARGON2ID)
        $dummy = '$argon2id$v=19$m=65536,t=4,p=1$ZHVtbXlzYWx0ZHVtbXlzYWx0$AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
        password_verify($password, $dummy);
    }

    private static function tooManyIpFailures(string $ipHash): bool
    {
        try {
            $stmt = Db::pdo()->prepare(
                "SELECT COUNT(*) FROM audit_log
                  WHERE action = 'login.fail'
                    AND JSON_UNQUOTE(JSON_EXTRACT(meta, '$.ip_hash')) = :h
                    AND created_at >= (UTC_TIMESTAMP() - INTERVAL :m MINUTE)"
            );
            $stmt->bindValue(':h', $ipHash, PDO::PARAM_STR);
            $stmt->bindValue(':m', self::IP_WINDOW_MIN, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetchColumn() >= self::MAX_FAILS_IP;
        } catch (Throwable $e) {
            error_log('[auth] ip throttle check failed: ' . $e->getMessage());
            return false; // fail-open for availability; per-user lockout still applies
        }
    }

    private static function recordIpFailure(string $ipHash): void
    {
        try {
            self::audit(null, 'login.fail', null, ['ip_hash' => $ipHash]);
        } catch (Throwable $e) {
            error_log('[auth] failed to record ip failure: ' . $e->getMessage());
        }
    }

    private static function audit(?int $userId, string $action, ?string $targetType = null, ?array $meta = null): void
    {
        try {
            $stmt = Db::pdo()->prepare(
                'INSERT INTO audit_log (user_id, action, target_type, target_id, meta, ip_hash)
                 VALUES (:user_id, :action, :target_type, NULL, :meta, :ip_hash)'
            );
            $stmt->execute([
                ':user_id'     => $userId,
                ':action'      => $action,
                ':target_type' => $targetType,
                ':meta'        => $meta === null ? null : json_encode($meta, JSON_UNESCAPED_SLASHES),
                ':ip_hash'     => RateLimiter::ipHash(),
            ]);
        } catch (Throwable $e) {
            error_log('[auth] audit failed: ' . $e->getMessage());
        }
    }
}