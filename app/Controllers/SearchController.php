<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * /search delegates to the gallery so search & filters share one code path.
 * The controller exists so /search remains a first-class URL for bookmarks.
 */
final class SearchController
{
    public function index(): void
    {
        (new ProjectController())->index();
    }
}