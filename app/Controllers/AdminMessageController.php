<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\RequireAuth;
use App\Services\Csrf;
use App\Services\Db;
use App\Services\Paginator;
use PDO;

final class AdminMessageController
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        RequireAuth::check();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $view = $_GET['view'] ?? 'inbox'; // inbox, spam, all

        $where = "is_spam = 0";
        if ($view === 'spam') $where = "is_spam = 1";
        elseif ($view === 'all') $where = "1=1";

        $pdo = Db::pdo();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE {$where}");
        $stmt->execute();
        $total = (int) $stmt->fetchColumn();

        $paginator = new Paginator($total, $page, self::PER_PAGE);
        $offset = ($page - 1) * self::PER_PAGE;

        $stmt = $pdo->prepare("
            SELECT id, name, email, subject, LEFT(message, 100) AS preview, 
                   is_read, is_spam, created_at 
            FROM contact_messages 
            WHERE {$where} 
            ORDER BY created_at DESC 
            LIMIT :lim OFFSET :off
        ");
        $stmt->bindValue(':lim', self::PER_PAGE, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $messages = $stmt->fetchAll();

        $title = 'Messages — Admin — GeoAtlas Bangladesh';
        $csrfAction = Csrf::token('admin_message_action');

        ob_start();
        require dirname(__DIR__) . '/Views/admin/messages/index.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    public function show(array $params): void
    {
        RequireAuth::check();
        $id = (int) ($params['id'] ?? 0);
        $pdo = Db::pdo();

        $stmt = $pdo->prepare('SELECT * FROM contact_messages WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $message = $stmt->fetch();

        if ($message === false) {
            http_response_code(404);
            flash('error', 'Message not found.');
            header('Location: ' . url('/admin/messages'), true, 303);
            return;
        }

        // Mark as read
        if (!$message['is_read']) {
            $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = :id')->execute([':id' => $id]);
            $message['is_read'] = 1;
        }

        $title = 'Message Details — Admin — GeoAtlas Bangladesh';
        $csrfAction = Csrf::token('admin_message_action');

        ob_start();
        require dirname(__DIR__) . '/Views/admin/messages/show.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    public function action(array $params): void
    {
        RequireAuth::check();
        $this->requirePost();
        $this->requireCsrf('admin_message_action');

        $id = (int) ($params['id'] ?? 0);
        $action = $_POST['action'] ?? ''; // mark_spam, mark_read, delete

        $pdo = Db::pdo();

        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM contact_messages WHERE id = :id')->execute([':id' => $id]);
            flash('success', 'Message deleted.');
        } elseif ($action === 'mark_spam') {
            $pdo->prepare('UPDATE contact_messages SET is_spam = 1, is_read = 1 WHERE id = :id')->execute([':id' => $id]);
            flash('success', 'Message marked as spam.');
        } elseif ($action === 'mark_read') {
            $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = :id')->execute([':id' => $id]);
            flash('success', 'Message marked as read.');
        }

        $redirect = $_POST['redirect'] ?? '/admin/messages';
        header('Location: ' . url($redirect), true, 303);
    }

    private function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            exit;
        }
    }

    private function requireCsrf(string $form): void
    {
        if (!Csrf::verify($form, $_POST['_csrf'] ?? null)) {
            http_response_code(422);
            flash('error', 'Your session expired. Please try again.');
            header('Location: ' . url('/admin/messages'), true, 303);
            exit;
        }
    }
}