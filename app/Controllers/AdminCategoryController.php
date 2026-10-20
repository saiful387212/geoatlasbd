<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\RequireAuth;
use App\Models\Category;
use App\Services\Csrf;
use App\Services\Slug;

final class AdminCategoryController
{
    public function index(): void
    {
        RequireAuth::check();
        $categories = Category::allWithTotals();
        $csrfAction = Csrf::token('admin_category_delete');
        $title      = 'Categories — Admin — GeoAtlas Bangladesh';

        ob_start();
        require dirname(__DIR__) . '/Views/admin/categories/index.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    public function create(): void
    {
        RequireAuth::check();
        $category = ['id' => null, 'name' => '', 'slug' => '', 'description' => '', 'color' => '#14B8A6', 'icon' => '', 'sort_order' => 0];
        $errors   = [];
        $title    = 'New Category — Admin — GeoAtlas Bangladesh';

        ob_start();
        require dirname(__DIR__) . '/Views/admin/categories/form.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    public function store(): void
    {
        RequireAuth::check();
        $this->requirePost();
        $this->requireCsrf('admin_category_form');

        $data   = $this->collect($_POST);
        $errors = $this->validate($data, null);

        if ($errors === []) {
            try {
                Category::create($data);
                flash('success', 'Category created.');
                header('Location: ' . url('/admin/categories'), true, 303);
                return;
            } catch (\Throwable $e) {
                error_log('[admin category store] ' . $e->getMessage());
                $errors['_form'] = 'Save failed. Please try again.';
            }
        }

        $category = $data;
        $title    = 'New Category — Admin — GeoAtlas Bangladesh';
        ob_start();
        require dirname(__DIR__) . '/Views/admin/categories/form.php';
        $content = ob_get_clean();
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    public function edit(array $params): void
    {
        RequireAuth::check();
        $id = (int) ($params['id'] ?? 0);
        $category = Category::find($id);
        if ($category === null) { $this->notFound(); return; }

        $errors = [];
        $title  = 'Edit Category — Admin — GeoAtlas Bangladesh';

        ob_start();
        require dirname(__DIR__) . '/Views/admin/categories/form.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    public function update(array $params): void
    {
        RequireAuth::check();
        $this->requirePost();
        $this->requireCsrf('admin_category_form');

        $id = (int) ($params['id'] ?? 0);
        $existing = Category::find($id);
        if ($existing === null) { $this->notFound(); return; }

        $data   = $this->collect($_POST);
        $errors = $this->validate($data, $id);

        if ($errors === []) {
            try {
                Category::update($id, $data);
                flash('success', 'Category updated.');
                header('Location: ' . url('/admin/categories'), true, 303);
                return;
            } catch (\Throwable $e) {
                error_log('[admin category update] ' . $e->getMessage());
                $errors['_form'] = 'Save failed. Please try again.';
            }
        }

        $category = array_merge($existing, $data);
        $title    = 'Edit Category — Admin — GeoAtlas Bangladesh';
        ob_start();
        require dirname(__DIR__) . '/Views/admin/categories/form.php';
        $content = ob_get_clean();
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    public function destroy(array $params): void
    {
        RequireAuth::check();
        $this->requirePost();
        $this->requireCsrf('admin_category_delete');

        $id = (int) ($params['id'] ?? 0);
        $existing = Category::find($id);
        if ($existing === null) { $this->notFound(); return; }

        $ok = Category::delete($id);
        if ($ok) {
            flash('success', 'Category deleted.');
        } else {
            flash('error', 'Category has projects assigned. Reassign them first, then delete.');
        }
        header('Location: ' . url('/admin/categories'), true, 303);
    }

    // ---- helpers ----

    private function collect(array $in): array
    {
        return [
            'name'        => $this->cleanStr($in['name']        ?? '', 80),
            'slug'        => $this->cleanStr($in['slug']        ?? '', 100),
            'description' => $this->cleanStrOrNull($in['description'] ?? '', 255),
            'color'       => $this->cleanColor($in['color'] ?? '#14B8A6'),
            'icon'        => $this->cleanStrOrNull($in['icon']  ?? '', 60),
            'sort_order'  => (int) ($in['sort_order'] ?? 0),
        ];
    }

    private function validate(array $data, ?int $ignoreId): array
    {
        $errors = [];
        if ($data['name'] === '') {
            $errors['name'] = 'Please enter a category name.';
        }
        if ($data['slug'] === '') {
            $data['slug'] = Slug::make($data['name'], null);
        }
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $data['slug']) || strlen($data['slug']) > 100) {
            $errors['slug'] = 'Slug must contain only lowercase letters, digits, and hyphens.';
        } elseif (Category::slugExists($data['slug'], $ignoreId)) {
            $errors['slug'] = 'That slug is already in use.';
        }
        return $errors;
    }

    private function cleanStr(mixed $v, int $max): string
    {
        if (!is_string($v)) return '';
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $v) ?? '';
        return mb_substr(trim($v), 0, $max);
    }

    private function cleanStrOrNull(mixed $v, int $max): ?string
    {
        $s = $this->cleanStr($v, $max);
        return $s === '' ? null : $s;
    }

    private function cleanColor(mixed $v): string
    {
        if (!is_string($v)) return '#14B8A6';
        $v = trim($v);
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $v)) return '#14B8A6';
        return strtoupper($v);
    }

    private function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo 'Method Not Allowed';
            exit;
        }
    }

    private function requireCsrf(string $form): void
    {
        if (!Csrf::verify($form, $_POST['_csrf'] ?? null)) {
            http_response_code(422);
            flash('error', 'Your session expired. Please try again.');
            header('Location: ' . url('/admin/categories'), true, 303);
            exit;
        }
    }

    private function notFound(): void
    {
        http_response_code(404);
        $title = 'Not Found — Admin';
        ob_start();
        ?>
        <section class="admin-page">
            <div class="admin-page__head">
                <p class="eyebrow">404</p>
                <h1>Category not found.</h1>
                <p><a class="btn btn--primary" href="<?= e(url('/admin/categories')) ?>">Back to Categories</a></p>
            </div>
        </section>
        <?php
        $content = ob_get_clean();
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }
}