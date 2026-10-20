<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\RequireAuth;
use App\Models\Category;
use App\Services\Csrf;
use App\Services\Db;
use App\Services\HtmlSanitizer;
use PDO;
use PDOException;
use RuntimeException;

final class AdminContentController
{
    public function projects(): void
    {
        $user = RequireAuth::check();
        $databaseError = null;
        $projects = [];
        $categories = [];
        $editProject = null;
        try {
            $pdo = Db::pdo();
            $projects = $pdo->query(
                'SELECT p.id, p.title, p.slug, p.status, p.featured, p.published_at,
                        c.name AS category_name
                   FROM projects p
              LEFT JOIN categories c ON c.id = p.category_id
               ORDER BY p.updated_at DESC, p.id DESC'
            )->fetchAll();
            $categories = Category::allWithProjectCounts();
            $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
            if ($editId !== null && $editId !== false && $editId > 0) {
                $stmt = $pdo->prepare(
                    'SELECT id, category_id, title, slug, summary, description,
                            study_area, tags, status, featured
                       FROM projects WHERE id = :id'
                );
                $stmt->execute([':id' => $editId]);
                $editProject = $stmt->fetch() ?: null;
            }
        } catch (PDOException | RuntimeException $e) {
            error_log('[admin projects] Load failed: ' . $e->getMessage());
            $databaseError = 'Project data is unavailable. Check the database connection and confirm the GeoAtlas schema is installed.';
        }
        $this->render('Projects', 'projects', compact('user', 'projects', 'categories', 'editProject', 'databaseError'));
    }

    public function saveProject(): void
    {
        $user = $this->authorizePost('admin_project');
        $title = $this->text($_POST['title'] ?? null, 180);
        $slug = strtolower($this->text($_POST['slug'] ?? null, 180));
        if ($slug === '') {
            $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)) ?? '', '-');
        }
        $summary = $this->text($_POST['summary'] ?? null, 500);
        $description = is_string($_POST['description'] ?? null)
            ? HtmlSanitizer::sanitize($_POST['description']) : '';
        $studyArea = $this->text($_POST['study_area'] ?? null, 255);
        $tags = $this->text($_POST['tags'] ?? null, 1000);
        $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $status = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';

        if ($title === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)
            || $summary === '' || !$categoryId || ($id !== false && $id !== null && $id < 1)) {
            $this->failAndRedirect('/admin/projects', 'Enter a title, valid unique slug, summary, and category.');
        }

        $fields = [
            ':category_id' => $categoryId,
            ':title' => $title,
            ':slug' => $slug,
            ':summary' => $summary,
            ':description' => $description,
            ':study_area' => $studyArea !== '' ? $studyArea : null,
            ':tags' => $tags !== '' ? $tags : null,
            ':status' => $status,
            ':featured' => isset($_POST['featured']) ? 1 : 0,
        ];

        try {
            $pdo = Db::pdo();
            if (is_int($id) && $id > 0) {
                $fields[':id'] = $id;
                $stmt = $pdo->prepare(
                    "UPDATE projects
                        SET category_id = :category_id, title = :title, slug = :slug,
                            summary = :summary, description = :description,
                            study_area = :study_area, tags = :tags, status = :status,
                            featured = :featured,
                            published_at = CASE WHEN :published_status = 'published'
                                THEN COALESCE(published_at, UTC_TIMESTAMP()) ELSE NULL END,
                            updated_at = UTC_TIMESTAMP()
                      WHERE id = :id"
                );
                $fields[':published_status'] = $status;
                $stmt->execute($fields);
            } else {
                $fields[':author_id'] = (int) $user['id'];
                $fields[':published_status'] = $status;
                $stmt = $pdo->prepare(
                    "INSERT INTO projects
                        (author_id, category_id, title, slug, summary, description,
                         study_area, tags, status, featured, published_at)
                     VALUES
                        (:author_id, :category_id, :title, :slug, :summary, :description,
                         :study_area, :tags, :status, :featured,
                         CASE WHEN :published_status = 'published' THEN UTC_TIMESTAMP() ELSE NULL END)"
                );
                $stmt->execute($fields);
            }
        } catch (PDOException | RuntimeException $e) {
            error_log('[admin projects] Save failed: ' . $e->getMessage());
            $this->failAndRedirect('/admin/projects', 'Could not save the project. Check that its slug is unique and the database schema is current.');
        }

        flash('success', 'Project saved.');
        $this->redirect('/admin/projects');
    }

    public function deleteProject(): void
    {
        $this->authorizePost('admin_project');
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            $this->failAndRedirect('/admin/projects', 'Select a valid project.');
        }
        try {
            $stmt = Db::pdo()->prepare('DELETE FROM projects WHERE id = :id');
            $stmt->execute([':id' => $id]);
        } catch (PDOException | RuntimeException $e) {
            error_log('[admin projects] Delete failed: ' . $e->getMessage());
            $this->failAndRedirect('/admin/projects', 'Could not delete the project. Remove or reassign dependent records first.');
        }
        flash('success', 'Project deleted.');
        $this->redirect('/admin/projects');
    }

    public function categories(): void
    {
        $user = RequireAuth::check();
        $databaseError = null;
        $categories = [];
        $editCategory = null;
        try {
            $categories = Category::allWithProjectCounts();
            $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
            if ($editId !== null && $editId !== false && $editId > 0) {
                $stmt = Db::pdo()->prepare(
                    'SELECT id, name, slug, description, color, icon, sort_order
                       FROM categories WHERE id = :id'
                );
                $stmt->execute([':id' => $editId]);
                $editCategory = $stmt->fetch() ?: null;
            }
        } catch (PDOException | RuntimeException $e) {
            error_log('[admin categories] Load failed: ' . $e->getMessage());
            $databaseError = 'Category data is unavailable. Check the database connection and confirm the GeoAtlas schema is installed.';
        }
        $this->render('Categories', 'categories', compact('user', 'categories', 'editCategory', 'databaseError'));
    }

    public function saveCategory(): void
    {
        $this->authorizePost('admin_category');
        $name = $this->text($_POST['name'] ?? null, 100);
        $slug = strtolower($this->text($_POST['slug'] ?? null, 100));
        if ($slug === '') {
            $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)) ?? '', '-');
        }
        $description = $this->text($_POST['description'] ?? null, 500);
        $color = $this->text($_POST['color'] ?? null, 7);
        $icon = $this->text($_POST['icon'] ?? null, 50);
        $sortOrder = filter_var($_POST['sort_order'] ?? '0', FILTER_VALIDATE_INT);
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($name === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)
            || !is_int($sortOrder) || ($color !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $color))
            || ($id !== false && $id !== null && $id < 1)) {
            $this->failAndRedirect('/admin/categories', 'Enter a name, valid unique slug, and six-digit hex color.');
        }
        $params = [
            ':name' => $name,
            ':slug' => $slug,
            ':description' => $description !== '' ? $description : null,
            ':color' => $color !== '' ? $color : null,
            ':icon' => $icon !== '' ? $icon : null,
            ':sort_order' => $sortOrder,
        ];
        try {
            if (is_int($id) && $id > 0) {
                $params[':id'] = $id;
                $stmt = Db::pdo()->prepare(
                    'UPDATE categories SET name = :name, slug = :slug, description = :description,
                            color = :color, icon = :icon, sort_order = :sort_order
                      WHERE id = :id'
                );
            } else {
                $stmt = Db::pdo()->prepare(
                    'INSERT INTO categories (name, slug, description, color, icon, sort_order)
                     VALUES (:name, :slug, :description, :color, :icon, :sort_order)'
                );
            }
            $stmt->execute($params);
        } catch (PDOException | RuntimeException $e) {
            error_log('[admin categories] Save failed: ' . $e->getMessage());
            $this->failAndRedirect('/admin/categories', 'Could not save the category. Check that its slug is unique and the database schema is current.');
        }
        flash('success', 'Category saved.');
        $this->redirect('/admin/categories');
    }

    public function deleteCategory(): void
    {
        $this->authorizePost('admin_category');
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            $this->failAndRedirect('/admin/categories', 'Select a valid category.');
        }
        try {
            $stmt = Db::pdo()->prepare('DELETE FROM categories WHERE id = :id');
            $stmt->execute([':id' => $id]);
        } catch (PDOException | RuntimeException $e) {
            error_log('[admin categories] Delete failed: ' . $e->getMessage());
            $this->failAndRedirect('/admin/categories', 'This category is still used by projects. Reassign those projects before deleting it.');
        }
        flash('success', 'Category deleted.');
        $this->redirect('/admin/categories');
    }

    public function messages(): void
    {
        $user = RequireAuth::check();
        $databaseError = null;
        $messages = [];
        try {
            $messages = Db::pdo()->query(
                'SELECT id, name, email, subject, message, is_read, is_spam, submitted_at
                   FROM contact_messages
               ORDER BY is_read ASC, submitted_at DESC, id DESC'
            )->fetchAll();
        } catch (PDOException | RuntimeException $e) {
            error_log('[admin messages] Load failed: ' . $e->getMessage());
            $databaseError = 'Messages are unavailable. Check the database connection and confirm the GeoAtlas schema is installed.';
        }
        $this->render('Messages', 'messages', compact('user', 'messages', 'databaseError'));
    }

    public function updateMessage(): void
    {
        $this->authorizePost('admin_message');
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $action = $_POST['action'] ?? '';
        if (!$id || $id < 1 || !in_array($action, ['read', 'unread', 'delete'], true)) {
            $this->failAndRedirect('/admin/messages', 'Select a valid message action.');
        }
        try {
            if ($action === 'delete') {
                $stmt = Db::pdo()->prepare('DELETE FROM contact_messages WHERE id = :id');
            } else {
                $stmt = Db::pdo()->prepare('UPDATE contact_messages SET is_read = :is_read WHERE id = :id');
                $stmt->bindValue(':is_read', $action === 'read' ? 1 : 0, PDO::PARAM_INT);
            }
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException | RuntimeException $e) {
            error_log('[admin messages] Update failed: ' . $e->getMessage());
            $this->failAndRedirect('/admin/messages', 'Could not update the message. Check the database schema and permissions.');
        }
        flash('success', $action === 'delete' ? 'Message deleted.' : 'Message status updated.');
        $this->redirect('/admin/messages');
    }

    private function authorizePost(string $form): array
    {
        $user = RequireAuth::check();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Method Not Allowed');
        }
        if (!Csrf::verify($form, is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
            http_response_code(422);
            $this->failAndRedirect('/admin/dashboard', 'Your session expired. Please try again.');
        }
        return $user;
    }

    private function render(string $section, string $view, array $data): void
    {
        $title = $section . ' — Admin — GeoAtlas Bangladesh';
        $csrfForm = match ($view) {
            'projects' => 'admin_project',
            'categories' => 'admin_category',
            default => 'admin_message',
        };
        $csrf = Csrf::token($csrfForm);
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/admin/' . $view . '.php';
        $content = ob_get_clean();
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    private function text(mixed $value, int $max): string
    {
        if (!is_string($value)) {
            return '';
        }
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        return mb_substr($value, 0, $max);
    }

    private function failAndRedirect(string $path, string $message): never
    {
        flash('error', $message);
        $this->redirect($path);
    }

    private function redirect(string $path): never
    {
        header('Location: ' . url($path), true, 303);
        exit;
    }
}
