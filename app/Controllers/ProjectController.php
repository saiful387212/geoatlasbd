<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Project;
use App\Services\Paginator;
use PDOException;
use RuntimeException;

final class ProjectController
{
    private const PER_PAGE = 9;

    public function index(): void
    {
        $categorySlug = $this->cleanSlug($_GET['category'] ?? null);
        $q            = $this->cleanQuery($_GET['q'] ?? null);
        $sort         = $this->cleanSort($_GET['sort'] ?? 'newest');
        $page         = $this->cleanPage($_GET['page'] ?? 1);

        $filters = ['category' => $categorySlug, 'q' => $q, 'sort' => $sort, 'page' => $page];
        $dataError = null;
        try {
            $result = Project::search($filters, self::PER_PAGE);
            $categories = Category::allWithProjectCounts();
        } catch (PDOException | RuntimeException $e) {
            error_log('[PROJECTS] Database-backed content is unavailable: ' . $e->getMessage());
            $result = ['rows' => [], 'total' => 0];
            $categories = [];
            $dataError = 'Project data is unavailable. Check the database credentials and confirm the GeoAtlas schema is installed.';
        }
        $paginator = new Paginator($result['total'], $page, self::PER_PAGE);

        $qs = [];
        if ($categorySlug !== null) $qs['category'] = $categorySlug;
        if ($q !== null)            $qs['q']        = $q;
        if ($sort !== 'newest')     $qs['sort']     = $sort;

        $title      = 'GIS Projects — GeoAtlas Bangladesh';
        $metaDescription = 'Browse published GIS, cartography, and remote-sensing projects across Bangladesh.';
        $activeCategory  = null;
        if ($categorySlug !== null) {
            foreach ($categories as $c) {
                if ($c['slug'] === $categorySlug) { $activeCategory = $c; break; }
            }
        }

        ob_start();
        require dirname(__DIR__) . '/Views/public/projects.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/public.php';
    }

    public function show(array $params): void
    {
        $slug = $this->cleanSlug($params['slug'] ?? null);
        if ($slug === null) {
            $this->notFound();
            return;
        }

        try {
            $project = Project::findPublishedBySlug($slug);
            if ($project !== null) {
                Project::incrementViews((int) $project['id']);
                $images  = Project::imagesFor((int) $project['id']);
                $files   = Project::filesFor((int) $project['id']);
                $related = Project::related((int) $project['id'], (int) $project['category_id'], 3);
                if ($related === []) {
                    $related = Project::relatedFallback((int) $project['id'], 3);
                }
            }
        } catch (PDOException | RuntimeException $e) {
            error_log('[PROJECT] Database-backed content is unavailable: ' . $e->getMessage());
            $this->unavailable();
            return;
        }

        if ($project === null) {
            $this->notFound();
            return;
        }

        $needsLeaflet = $project['latitude'] !== null && $project['longitude'] !== null;

        $title           = $project['title'] . ' — GeoAtlas Bangladesh';
        $metaDescription = $project['summary'];

        ob_start();
        require dirname(__DIR__) . '/Views/public/project.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/public.php';
    }

    private function notFound(): void
    {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        $title = 'Project Not Found — GeoAtlas Bangladesh';
        ob_start();
        require dirname(__DIR__) . '/Views/public/partials/not-found.php';
        $content = ob_get_clean();
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/public.php';
    }

    private function unavailable(): void
    {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        $title = 'Projects temporarily unavailable — GeoAtlas Bangladesh';
        $metaDescription = 'Project information is temporarily unavailable.';
        ob_start();
        ?>
        <section class="page-hero">
            <div class="container">
                <p class="eyebrow">Temporarily unavailable</p>
                <h1 class="page-hero__title">Project data is unavailable</h1>
                <p class="page-hero__lead" role="alert">
                    Check the database credentials and confirm the GeoAtlas schema is installed.
                </p>
                <p><a class="btn btn--primary" href="<?= e(url('/')) ?>">Back to Home</a></p>
            </div>
        </section>
        <?php
        $content = ob_get_clean();
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/public.php';
    }

    private function cleanSlug(mixed $v): ?string
    {
        if (!is_string($v)) return null;
        $v = trim($v);
        if ($v === '' || strlen($v) > 100) return null;
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $v) ? $v : null;
    }

    private function cleanQuery(mixed $v): ?string
    {
        if (!is_string($v)) return null;
        $v = trim($v);
        if ($v === '' || mb_strlen($v) > 100) return null;
        return $v;
    }

    private function cleanSort(mixed $v): string
    {
        return in_array($v, ['newest', 'oldest', 'title'], true) ? (string) $v : 'newest';
    }

    private function cleanPage(mixed $v): int
    {
        $n = (int) $v;
        return $n >= 1 && $n <= 10000 ? $n : 1;
    }
}