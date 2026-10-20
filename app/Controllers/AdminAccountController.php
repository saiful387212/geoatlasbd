<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\RequireAuth;
use App\Models\User;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Db;

final class AdminAccountController
{
    public function showPasswordForm(): void
    {
        $user = RequireAuth::check();
        
        $title = 'Change Password — Admin';
        $csrf = Csrf::token('admin_password_form');
        $errors = $_SESSION['_password_errors'] ?? [];
        unset($_SESSION['_password_errors']);

        ob_start();
        require dirname(__DIR__) . '/Views/admin/account/password.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    public function updatePassword(): void
    {
        $user = RequireAuth::check();
        $this->requirePost();
        
        if (!Csrf::verify('admin_password_form', $_POST['_csrf'] ?? null)) {
            http_response_code(422);
            flash('error', 'Your session expired. Please try again.');
            header('Location: ' . url('/admin/account/password'), true, 303);
            exit;
        }

        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $errors = [];

        // 1. Verify current password
        $dbUser = User::findById((int) $user['id']);
        if (!$dbUser || !password_verify($current, $dbUser['password_hash'])) {
            $errors['current_password'] = 'Your current password is incorrect.';
        }

        // 2. Validate new password
        if (strlen($new) < 12) {
            $errors['new_password'] = 'New password must be at least 12 characters long.';
        } elseif ($new === $current) {
            $errors['new_password'] = 'New password cannot be the same as the current password.';
        } elseif ($new !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if ($errors !== []) {
            $_SESSION['_password_errors'] = $errors;
            header('Location: ' . url('/admin/account/password'), true, 303);
            exit;
        }

        // 3. Update password
        try {
            $hash = password_hash($new, PASSWORD_ARGON2ID);
            $stmt = Db::pdo()->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
            $stmt->execute([':h' => $hash, ':id' => $user['id']]);

            // Log the action
            $audit = Db::pdo()->prepare('INSERT INTO audit_log (user_id, action, ip_hash) VALUES (:u, "password.change", :ip)');
            $audit->execute([
                ':u' => $user['id'],
                ':ip' => hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . $config['app']['key'])
            ]);

            flash('success', 'Your password has been changed successfully.');
            
            // Force logout and re-login for security
            Auth::logout();
            header('Location: ' . url('/admin/login'), true, 303);
            exit;

        } catch (\Throwable $e) {
            error_log('[admin password change] ' . $e->getMessage());
            $errors['_form'] = 'An error occurred while updating the password. Please try again.';
            $_SESSION['_password_errors'] = $errors;
            header('Location: ' . url('/admin/account/password'), true, 303);
            exit;
        }
    }

    private function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            exit;
        }
    }
}