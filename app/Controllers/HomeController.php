<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Db;
use App\Models\Category;
use App\Models\Project;
use PDOException;
use RuntimeException;

final class HomeController
{
    public function show(): void
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $dataError = null;

        try {
            Db::init($config['db']);
            $featured = Project::featured(3);
            // Fallback to latest published if nothing is flagged featured — never fake data.
            $hasFeatured = $featured !== [];
            if (!$hasFeatured) {
                $featured = Project::latestPublished(3);
            }
            $categories = Category::allWithProjectCounts();
        } catch (PDOException | RuntimeException $e) {
            error_log('[HOME] Database-backed content is unavailable: ' . $e->getMessage());
            $featured = [];
            $categories = [];
            $hasFeatured = false;
            $dataError = 'Project and category data are unavailable. Check the database credentials and confirm the GeoAtlas schema is installed.';
        }

        $title = 'GeoAtlas Bangladesh — Understanding Bangladesh Through Maps';
        $metaDescription = 'A personal GIS portfolio exploring Bangladesh through cartography, remote sensing, and geographic research.';

        ob_start();
        require dirname(__DIR__) . '/Views/public/home.php';
        $content = ob_get_clean();

        require dirname(__DIR__) . '/Views/layouts/public.php';
    }
}