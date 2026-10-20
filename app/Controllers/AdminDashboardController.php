<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\RequireAuth;
use App\Services\Csrf;
use App\Services\Db;

final class AdminDashboardController
{
    public function index(): void
    {
        $user = RequireAuth::check();

        $stats = [
            'projects'        => 0,
            'published'       => 0,
            'drafts'          => 0,
            'categories'      => 0,
            'unread_messages' => 0,
            'featured'        => 0,
        ];
        try {
            $pdo = Db::pdo();
            $stats['projects']        = (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
            $stats['published']       = (int) $pdo->query("SELECT COUNT(*) FROM projects WHERE status='published'")->fetchColumn();
            $stats['drafts']          = (int) $pdo->query("SELECT COUNT(*) FROM projects WHERE status='draft'")->fetchColumn();
            $stats['categories']      = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
            $stats['unread_messages'] = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_spam=0 AND is_read=0')->fetchColumn();
            $stats['featured']        = (int) $pdo->query('SELECT COUNT(*) FROM projects WHERE featured=1 AND status="published"')->fetchColumn();
        } catch (\Throwable $e) {
            error_log('[admin dashboard] ' . $e->getMessage());
        }

        $recent = [];
        try {
            $stmt = Db::pdo()->query(
                "SELECT id, title, slug, status, updated_at
                   FROM projects ORDER BY updated_at DESC LIMIT 5"
            );
            $recent = $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('[admin dashboard recent] ' . $e->getMessage());
        }

        $title = 'Dashboard — Admin — GeoAtlas Bangladesh';

        ob_start();
        require dirname(__DIR__) . '/Views/admin/dashboard.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }
}