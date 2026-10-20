<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Csrf;
use App\Services\RateLimiter;

final class AuthController
{
    private const FORM = 'admin_login';

    public function showLogin(): void
    {
        // Already logged in? Send to dashboard.
        if (Auth::check()) {
            header('Location: ' . url('/admin/dashboard'), true, 303);
            exit;
        }

        $csrf  = Csrf::token(self::FORM);
        $error = null;

        // Optional one-shot error from redirect (survives a single render).
        if (isset($_SESSION['_login_error'])) {
            $error = (string) $_SESSION['_login_error'];
            unset($_SESSION['_login_error']);
        }

        $title = 'Admin Login — GeoAtlas Bangladesh';

        ob_start();
        require dirname(__DIR__) . '/Views/admin/login.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    public function login(): void
    {
        if (!Csrf::verify(self::FORM, $_POST['_csrf'] ?? null)) {
            http_response_code(422);
            $_SESSION['_login_error'] = 'Your session expired. Please try again.';
            header('Location: ' . url('/admin/login'), true, 303);
            exit;
        }

        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        // Server-side length cap and shape check; still generic on failure.
        if ($email === '' || strlen($email) > 255 || $password === '' || strlen($password) > 4096) {
            $_SESSION['_login_error'] = 'Invalid email or password.';
            header('Location: ' . url('/admin/login'), true, 303);
            exit;
        }

        $ok = Auth::attempt($email, $password, RateLimiter::ipHash());

        if (!$ok) {
            // Deliberate small delay to further frustrate automated enumeration.
            usleep(random_int(120_000, 320_000));
            $_SESSION['_login_error'] = 'Invalid email or password.';
            header('Location: ' . url('/admin/login'), true, 303);
            exit;
        }

        Csrf::rotate(self::FORM);

        $intended = $_SESSION['_intended'] ?? null;
        unset($_SESSION['_intended']);

        // Same-origin path only.
        $dest = '/admin/dashboard';
        if (is_string($intended) && str_starts_with($intended, '/admin') && !str_starts_with($intended, '//')) {
            $dest = $intended;
        }

        header('Location: ' . url($dest), true, 303);
        exit;
    }

    public function logout(): void
    {
        // POST-only — GET logouts are vulnerable to CSRF.
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo 'Method Not Allowed';
            return;
        }

        if (!Csrf::verify('admin_logout', $_POST['_csrf'] ?? null)) {
            http_response_code(422);
            header('Location: ' . url('/admin/dashboard'), true, 303);
            exit;
        }

        Auth::logout();
        Csrf::rotate('admin_logout');

        flash('success', 'You have been signed out.');
        header('Location: ' . url('/admin/login'), true, 303);
        exit;
    }
}