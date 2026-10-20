<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\RequireAuth;
use App\Models\Category;
use App\Models\MediaAudit;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectImage;
use App\Services\Csrf;
use App\Services\Paginator;
use App\Services\Slug;
use App\Services\Uploader;

final class AdminProjectController
{
    private const PER_PAGE = 20;
    private const MAX_GALLERY_PER_REQUEST = 20;

    // ============================================================
    // LIST
    // ============================================================

    public function index(): void
    {
        $user = RequireAuth::check();

        $q        = $this->cleanStr($_GET['q']        ?? '', 100);
        $status   = in_array($_GET['status'] ?? '', ['draft','published'], true) ? $_GET['status'] : null;
        $category = $this->cleanStr($_GET['category'] ?? '', 100);
        $page     = max(1, (int) ($_GET['page'] ?? 1));

        $filters = ['q' => $q ?: null, 'status' => $status, 'category' => $category ?: null, 'page' => $page];

        $result    = Project::adminList($filters, self::PER_PAGE);
        $paginator = new Paginator($result['total'], $page, self::PER_PAGE);

        $qs = array_filter(['q' => $q, 'status' => $status, 'category' => $category]);
        $categories = Category::allWithTotals();

        $title = 'Projects — Admin — GeoAtlas Bangladesh';

        ob_start();
        require dirname(__DIR__) . '/Views/admin/projects/index.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    // ============================================================
    // CREATE
    // ============================================================

    public function create(): void
    {
        $user = RequireAuth::check();

        $project    = $this->emptyProject();
        $errors     = [];
        $images     = [];
        $files      = [];
        $categories = Category::allWithTotals();
        $title      = 'New Project — Admin — GeoAtlas Bangladesh';

        ob_start();
        require dirname(__DIR__) . '/Views/admin/projects/form.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    public function store(): void
    {
        $user = RequireAuth::check();
        $this->requirePost();
        $this->requireCsrf('admin_project_form');

        $data   = $this->collectFormData($_POST);
        $errors = $this->validate($data, null);

        $imagesDir   = $this->imagesDir();
        $pdfsDir     = $this->pdfsDir();
        $quarantine  = $this->quarantineDir();
        $baseUploads = $this->baseUploadsDir();

        // Track anything written to disk so we can clean up on failure.
        $savedCover   = null;   // ['relative'=>..,'width'=>..,'height'=>..,'sha256'=>..,'size'=>..]
        $savedGallery = [];     // list of the same shape
        $savedPdf     = null;   // ['relative'=>..,'sha256'=>..,'size'=>..,'original_name'=>..]
        $coverRejected = false;

        try {
            if ($errors === []) {
                // ---------------- Cover image ----------------
                if (isset($_FILES['cover_image']) && ($_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    try {
                        $res = Uploader::saveImageStrict($_FILES['cover_image'], $imagesDir, true);
                        $savedCover          = $res;
                        $data['cover_image'] = $res['relative'];

                        MediaAudit::log(
                            (int) $user['id'], null, 'upload', 'image', $res['relative'],
                            (string) ($_FILES['cover_image']['name'] ?? ''),
                            (string) ($_FILES['cover_image']['type'] ?? ''),
                            'image/' . pathinfo($res['relative'], PATHINFO_EXTENSION),
                            $res['size'], $res['width'], $res['height'], $res['sha256'],
                            'ok', null
                        );
                    } catch (\RuntimeException $e) {
                        Uploader::quarantine($_FILES['cover_image'], $quarantine, 'cover: ' . $e->getMessage());
                        MediaAudit::log(
                            (int) $user['id'], null, 'upload', 'image', '(quarantined)',
                            (string) ($_FILES['cover_image']['name'] ?? ''),
                            (string) ($_FILES['cover_image']['type'] ?? ''),
                            null, 0, null, null, null, 'rejected', $e->getMessage()
                        );
                        $errors['cover_image'] = $e->getMessage();
                        $coverRejected = true;
                    }
                }

                // ---------------- Gallery ----------------
                if (!$coverRejected && !empty($_FILES['gallery']['name'][0])) {
                    $count = is_array($_FILES['gallery']['name']) ? count($_FILES['gallery']['name']) : 0;
                    if ($count > self::MAX_GALLERY_PER_REQUEST) {
                        $errors['gallery'] = 'Please upload at most ' . self::MAX_GALLERY_PER_REQUEST . ' gallery images at a time.';
                    } else {
                        foreach ($_FILES['gallery']['name'] as $i => $name) {
                            if (($_FILES['gallery']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                            $part = [
                                'name'     => $_FILES['gallery']['name'][$i],
                                'type'     => $_FILES['gallery']['type'][$i] ?? '',
                                'tmp_name' => $_FILES['gallery']['tmp_name'][$i],
                                'error'    => $_FILES['gallery']['error'][$i],
                                'size'     => $_FILES['gallery']['size'][$i],
                            ];
                            try {
                                $res = Uploader::saveImageStrict($part, $imagesDir, true);
                                $savedGallery[] = $res;
                                MediaAudit::log(
                                    (int) $user['id'], null, 'upload', 'image', $res['relative'],
                                    (string) $part['name'], (string) $part['type'],
                                    'image/' . pathinfo($res['relative'], PATHINFO_EXTENSION),
                                    $res['size'], $res['width'], $res['height'], $res['sha256'],
                                    'ok', null
                                );
                            } catch (\RuntimeException $e) {
                                Uploader::quarantine($part, $quarantine, 'gallery: ' . $e->getMessage());
                                MediaAudit::log(
                                    (int) $user['id'], null, 'upload', 'image', '(quarantined)',
                                    (string) $part['name'], (string) $part['type'],
                                    null, 0, null, null, null, 'rejected', $e->getMessage()
                                );
                                $errors['gallery'] = 'One image was rejected: ' . $e->getMessage();
                                // Stop processing further gallery images on first failure —
                                // the transaction below will roll back everything anyway.
                                break;
                            }
                        }
                    }
                }

                // ---------------- PDF ----------------
                if (!$coverRejected && !isset($errors['gallery']) &&
                    isset($_FILES['report_pdf']) && ($_FILES['report_pdf']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    try {
                        $res = Uploader::savePdfStrict($_FILES['report_pdf'], $pdfsDir);
                        $savedPdf = $res + [
                            'original_name' => basename((string) ($_FILES['report_pdf']['name'] ?? 'report.pdf')),
                        ];
                        MediaAudit::log(
                            (int) $user['id'], null, 'upload', 'pdf', $res['relative'],
                            $savedPdf['original_name'], (string) ($_FILES['report_pdf']['type'] ?? ''),
                            'application/pdf', $res['size'], null, null, $res['sha256'],
                            'ok', null
                        );
                    } catch (\RuntimeException $e) {
                        Uploader::quarantine($_FILES['report_pdf'], $quarantine, 'pdf: ' . $e->getMessage());
                        MediaAudit::log(
                            (int) $user['id'], null, 'upload', 'pdf', '(quarantined)',
                            (string) ($_FILES['report_pdf']['name'] ?? ''),
                            (string) ($_FILES['report_pdf']['type'] ?? ''),
                            null, 0, null, null, null, 'rejected', $e->getMessage()
                        );
                        $errors['report_pdf'] = $e->getMessage();
                    }
                }

                // ---------------- Persist ----------------
                if ($errors === []) {
                    $data['author_id'] = (int) $user['id'];
                    $projectId         = Project::create($data);

                    foreach ($savedGallery as $i => $g) {
                        ProjectImage::add($projectId, $g['relative'], null, $i);
                    }
                    if ($savedPdf !== null) {
                        ProjectFile::add(
                            $projectId,
                            $savedPdf['original_name'],
                            $savedPdf['relative'],
                            'application/pdf',
                            $savedPdf['size']
                        );
                    }

                    flash('success', 'Project created.');
                    header('Location: ' . url('/admin/projects/' . $projectId . '/edit'), true, 303);
                    return;
                }
            }
        } catch (\Throwable $e) {
            error_log('[admin project store] ' . $e->getMessage());
            // Roll back any files written but not committed to the DB.
            if ($savedCover !== null) Uploader::deleteSafe($baseUploads, $savedCover['relative']);
            foreach ($savedGallery as $g) Uploader::deleteSafe($baseUploads, $g['relative']);
            if ($savedPdf !== null)   Uploader::deleteSafe($baseUploads, $savedPdf['relative']);
            $errors['_form'] = 'An error prevented the project from being saved. No changes were made.';
        }

        // If we got here with field errors after successful file writes, clean the orphan files.
        // (Case: cover saved OK, then PDF rejected → nothing committed to DB.)
        if ($savedCover !== null && $errors !== [])   Uploader::deleteSafe($baseUploads, $savedCover['relative']);
        if ($savedPdf   !== null && $errors !== [])   Uploader::deleteSafe($baseUploads, $savedPdf['relative']);
        foreach ($savedGallery as $g) {
            if ($errors !== []) Uploader::deleteSafe($baseUploads, $g['relative']);
        }

        // Re-render form with values + errors.
        $project    = $data + $this->emptyProject();
        $images     = [];
        $files      = [];
        $categories = Category::allWithTotals();
        $title      = 'New Project — Admin — GeoAtlas Bangladesh';

        ob_start();
        require dirname(__DIR__) . '/Views/admin/projects/form.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    // ============================================================
    // EDIT
    // ============================================================

    public function edit(array $params): void
    {
        $user = RequireAuth::check();
        $id = (int) ($params['id'] ?? 0);

        $project = Project::adminFind($id);
        if ($project === null) {
            $this->notFound('Project not found.');
            return;
        }

        $images     = ProjectImage::forProject($id);
        $files      = ProjectFile::forProject($id);
        $errors     = [];
        $categories = Category::allWithTotals();
        $title      = 'Edit Project — Admin — GeoAtlas Bangladesh';

        ob_start();
        require dirname(__DIR__) . '/Views/admin/projects/form.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    public function update(array $params): void
    {
        $user = RequireAuth::check();
        $this->requirePost();
        $this->requireCsrf('admin_project_form');

        $id = (int) ($params['id'] ?? 0);
        $project = Project::adminFind($id);
        if ($project === null) {
            $this->notFound('Project not found.');
            return;
        }

        $data   = $this->collectFormData($_POST);
        $errors = $this->validate($data, $id);

        $imagesDir   = $this->imagesDir();
        $pdfsDir     = $this->pdfsDir();
        $quarantine  = $this->quarantineDir();
        $baseUploads = $this->baseUploadsDir();

        $newCover     = null;
        $newGallery   = [];
        $newPdf       = null;
        $coverChanged = false;
        $oldCoverPath = (string) ($project['cover_image'] ?? '');

        try {
            if ($errors === []) {
                // ---------------- Cover ----------------
                if (isset($_FILES['cover_image']) && ($_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    try {
                        $res = Uploader::saveImageStrict($_FILES['cover_image'], $imagesDir, true);
                        $newCover            = $res;
                        $data['cover_image'] = $res['relative'];
                        $coverChanged        = true;
                        MediaAudit::log(
                            (int) $user['id'], $id, 'replace', 'image', $res['relative'],
                            (string) ($_FILES['cover_image']['name'] ?? ''),
                            (string) ($_FILES['cover_image']['type'] ?? ''),
                            'image/' . pathinfo($res['relative'], PATHINFO_EXTENSION),
                            $res['size'], $res['width'], $res['height'], $res['sha256'],
                            'ok', null
                        );
                    } catch (\RuntimeException $e) {
                        Uploader::quarantine($_FILES['cover_image'], $quarantine, 'cover: ' . $e->getMessage());
                        MediaAudit::log(
                            (int) $user['id'], $id, 'replace', 'image', '(quarantined)',
                            (string) ($_FILES['cover_image']['name'] ?? ''),
                            (string) ($_FILES['cover_image']['type'] ?? ''),
                            null, 0, null, null, null, 'rejected', $e->getMessage()
                        );
                        $errors['cover_image'] = $e->getMessage();
                    }
                } elseif (isset($_POST['remove_cover']) && $_POST['remove_cover'] === '1') {
                    $data['cover_image'] = null;
                    $coverChanged        = true;
                } else {
                    $data['cover_image'] = $project['cover_image'];
                }

                // ---------------- Gallery ----------------
                if (!isset($errors['cover_image']) && !empty($_FILES['gallery']['name'][0])) {
                    $count = is_array($_FILES['gallery']['name']) ? count($_FILES['gallery']['name']) : 0;
                    if ($count > self::MAX_GALLERY_PER_REQUEST) {
                        $errors['gallery'] = 'Please upload at most ' . self::MAX_GALLERY_PER_REQUEST . ' gallery images at a time.';
                    } else {
                        foreach ($_FILES['gallery']['name'] as $i => $name) {
                            if (($_FILES['gallery']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                            $part = [
                                'name'     => $_FILES['gallery']['name'][$i],
                                'type'     => $_FILES['gallery']['type'][$i] ?? '',
                                'tmp_name' => $_FILES['gallery']['tmp_name'][$i],
                                'error'    => $_FILES['gallery']['error'][$i],
                                'size'     => $_FILES['gallery']['size'][$i],
                            ];
                            try {
                                $res = Uploader::saveImageStrict($part, $imagesDir, true);
                                $newGallery[] = $res;
                                MediaAudit::log(
                                    (int) $user['id'], $id, 'upload', 'image', $res['relative'],
                                    (string) $part['name'], (string) $part['type'],
                                    'image/' . pathinfo($res['relative'], PATHINFO_EXTENSION),
                                    $res['size'], $res['width'], $res['height'], $res['sha256'],
                                    'ok', null
                                );
                            } catch (\RuntimeException $e) {
                                Uploader::quarantine($part, $quarantine, 'gallery: ' . $e->getMessage());
                                MediaAudit::log(
                                    (int) $user['id'], $id, 'upload', 'image', '(quarantined)',
                                    (string) $part['name'], (string) $part['type'],
                                    null, 0, null, null, null, 'rejected', $e->getMessage()
                                );
                                $errors['gallery'] = 'One image was rejected: ' . $e->getMessage();
                                break;
                            }
                        }
                    }
                }

                // ---------------- PDF ----------------
                if (!isset($errors['cover_image']) && !isset($errors['gallery']) &&
                    isset($_FILES['report_pdf']) && ($_FILES['report_pdf']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    try {
                        $res = Uploader::savePdfStrict($_FILES['report_pdf'], $pdfsDir);
                        $newPdf = $res + [
                            'original_name' => basename((string) ($_FILES['report_pdf']['name'] ?? 'report.pdf')),
                        ];
                        MediaAudit::log(
                            (int) $user['id'], $id, 'upload', 'pdf', $res['relative'],
                            $newPdf['original_name'], (string) ($_FILES['report_pdf']['type'] ?? ''),
                            'application/pdf', $res['size'], null, null, $res['sha256'],
                            'ok', null
                        );
                    } catch (\RuntimeException $e) {
                        Uploader::quarantine($_FILES['report_pdf'], $quarantine, 'pdf: ' . $e->getMessage());
                        MediaAudit::log(
                            (int) $user['id'], $id, 'upload', 'pdf', '(quarantined)',
                            (string) ($_FILES['report_pdf']['name'] ?? ''),
                            (string) ($_FILES['report_pdf']['type'] ?? ''),
                            null, 0, null, null, null, 'rejected', $e->getMessage()
                        );
                        $errors['report_pdf'] = $e->getMessage();
                    }
                }

                // ---------------- Persist ----------------
                if ($errors === []) {
                    Project::update($id, $data);

                    foreach ($newGallery as $i => $g) {
                        ProjectImage::add($id, $g['relative'], null, $i);
                    }
                    if ($newPdf !== null) {
                        ProjectFile::add(
                            $id,
                            $newPdf['original_name'],
                            $newPdf['relative'],
                            'application/pdf',
                            $newPdf['size']
                        );
                    }

                    // Delete old cover after successful save (only if it actually changed).
                    if ($coverChanged && $oldCoverPath !== '' && $oldCoverPath !== (string) $data['cover_image']) {
                        if (Uploader::deleteSafe($baseUploads, $oldCoverPath)) {
                            MediaAudit::log(
                                (int) $user['id'], $id, 'delete', 'image', $oldCoverPath,
                                null, null, null, 0, null, null, null, 'ok', 'cover replaced'
                            );
                        }
                    }

                    flash('success', 'Project updated.');
                    header('Location: ' . url('/admin/projects/' . $id . '/edit'), true, 303);
                    return;
                }
            }
        } catch (\Throwable $e) {
            error_log('[admin project update] ' . $e->getMessage());
            if ($newCover !== null)   Uploader::deleteSafe($baseUploads, $newCover['relative']);
            foreach ($newGallery as $g) Uploader::deleteSafe($baseUploads, $g['relative']);
            if ($newPdf !== null)     Uploader::deleteSafe($baseUploads, $newPdf['relative']);
            $errors['_form'] = 'An error prevented changes from being saved. No changes were made.';
        }

        // Post-error orphan cleanup for files that were saved but didn't commit.
        if ($errors !== []) {
            if ($newCover !== null) Uploader::deleteSafe($baseUploads, $newCover['relative']);
            foreach ($newGallery as $g) Uploader::deleteSafe($baseUploads, $g['relative']);
            if ($newPdf !== null)   Uploader::deleteSafe($baseUploads, $newPdf['relative']);
        }

        // Re-render form.
        $data['id']          = $id;
        $data['cover_image'] = $data['cover_image'] ?? $project['cover_image'];
        $project             = array_merge($project, $data);
        $images              = ProjectImage::forProject($id);
        $files               = ProjectFile::forProject($id);
        $categories          = Category::allWithTotals();
        $title               = 'Edit Project — Admin — GeoAtlas Bangladesh';

        ob_start();
        require dirname(__DIR__) . '/Views/admin/projects/form.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    // ============================================================
    // DELETE
    // ============================================================

    public function confirmDelete(array $params): void
    {
        $user = RequireAuth::check();
        $id = (int) ($params['id'] ?? 0);
        $project = Project::adminFind($id);
        if ($project === null) {
            $this->notFound('Project not found.');
            return;
        }
        $title = 'Delete Project — Admin — GeoAtlas Bangladesh';
        $csrf  = Csrf::token('admin_project_delete');

        ob_start();
        require dirname(__DIR__) . '/Views/admin/projects/delete.php';
        $content = ob_get_clean();

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }

    public function destroy(array $params): void
    {
        $user = RequireAuth::check();
        $this->requirePost();
        $this->requireCsrf('admin_project_delete');

        $id = (int) ($params['id'] ?? 0);
        $project = Project::adminFind($id);
        if ($project === null) {
            $this->notFound('Project not found.');
            return;
        }

        $baseUploads = $this->baseUploadsDir();

        try {
            $files = Project::delete($id);
        } catch (\Throwable $e) {
            error_log('[admin project delete] ' . $e->getMessage());
            flash('error', 'Could not delete the project. No changes were made.');
            header('Location: ' . url('/admin/projects'), true, 303);
            return;
        }

        // Post-commit disk cleanup.
        if (!empty($files['cover_image'])) {
            if (Uploader::deleteSafe($baseUploads, (string) $files['cover_image'])) {
                MediaAudit::log(
                    (int) $user['id'], $id, 'delete', 'image', (string) $files['cover_image'],
                    null, null, null, 0, null, null, null, 'ok', 'project deleted'
                );
            }
        }
        foreach ($files['images'] as $p) {
            if (Uploader::deleteSafe($baseUploads, (string) $p)) {
                MediaAudit::log(
                    (int) $user['id'], $id, 'delete', 'image', (string) $p,
                    null, null, null, 0, null, null, null, 'ok', 'project deleted'
                );
            }
        }
        foreach ($files['files'] as $p) {
            if (Uploader::deleteSafe($baseUploads, (string) $p)) {
                MediaAudit::log(
                    (int) $user['id'], $id, 'delete', 'pdf', (string) $p,
                    null, null, null, 0, null, null, null, 'ok', 'project deleted'
                );
            }
        }

        flash('success', 'Project deleted.');
        header('Location: ' . url('/admin/projects'), true, 303);
    }

    // ============================================================
    // TOGGLES (still POST)
    // ============================================================

    public function togglePublish(array $params): void
    {
        RequireAuth::check();
        $this->requirePost();
        $this->requireCsrf('admin_project_toggle');

        $id = (int) ($params['id'] ?? 0);
        $newStatus = ($_POST['to'] ?? '') === 'published' ? 'published' : 'draft';
        $project = Project::adminFind($id);
        if ($project === null) { $this->notFound('Project not found.'); return; }

        Project::setStatus($id, $newStatus);
        flash('success', $newStatus === 'published' ? 'Project published.' : 'Project set to draft.');
        header('Location: ' . url('/admin/projects'), true, 303);
    }

    public function toggleFeatured(array $params): void
    {
        RequireAuth::check();
        $this->requirePost();
        $this->requireCsrf('admin_project_toggle');

        $id = (int) ($params['id'] ?? 0);
        $feature = ($_POST['featured'] ?? '') === '1';
        $project = Project::adminFind($id);
        if ($project === null) { $this->notFound('Project not found.'); return; }

        Project::setFeatured($id, $feature);
        flash('success', $feature ? 'Project marked as featured.' : 'Featured flag removed.');
        header('Location: ' . url('/admin/projects'), true, 303);
    }

    // ============================================================
    // GALLERY / PDF REMOVAL (still POST)
    // ============================================================

    public function destroyImage(array $params): void
    {
        $user = RequireAuth::check();
        $this->requirePost();
        $this->requireCsrf('admin_project_media');

        $imageId   = (int) ($params['imageId'] ?? 0);
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $baseUploads = $this->baseUploadsDir();

        $path = ProjectImage::delete($imageId);
        if ($path !== null) {
            Uploader::deleteSafe($baseUploads, $path);
            MediaAudit::log(
                (int) $user['id'], $projectId ?: null, 'delete', 'image', $path,
                null, null, null, 0, null, null, null, 'ok', 'removed by user'
            );
            flash('success', 'Image removed.');
        }
        header('Location: ' . url('/admin/projects/' . $projectId . '/edit'), true, 303);
    }

    public function destroyFile(array $params): void
    {
        $user = RequireAuth::check();
        $this->requirePost();
        $this->requireCsrf('admin_project_media');

        $fileId    = (int) ($params['fileId'] ?? 0);
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $baseUploads = $this->baseUploadsDir();

        $path = ProjectFile::delete($fileId);
        if ($path !== null) {
            Uploader::deleteSafe($baseUploads, $path);
            MediaAudit::log(
                (int) $user['id'], $projectId ?: null, 'delete', 'pdf', $path,
                null, null, null, 0, null, null, null, 'ok', 'removed by user'
            );
            flash('success', 'Report removed.');
        }
        header('Location: ' . url('/admin/projects/' . $projectId . '/edit'), true, 303);
    }

    // ============================================================
    // HELPERS — paths
    // ============================================================

    private function imagesDir(): string     { return dirname(__DIR__, 2) . '/public/uploads/images'; }
    private function pdfsDir(): string       { return dirname(__DIR__, 2) . '/public/uploads/pdfs'; }
    private function baseUploadsDir(): string{ return dirname(__DIR__, 2) . '/public/uploads'; }
    private function quarantineDir(): string { return dirname(__DIR__, 2) . '/storage/quarantine'; }

    // ============================================================
    // HELPERS — validation
    // ============================================================

    private function emptyProject(): array
    {
        return [
            'id' => null, 'category_id' => null, 'author_id' => null,
            'title' => '', 'slug' => '', 'summary' => '', 'description' => '',
            'objectives' => '', 'research_questions' => '', 'data_sources' => '', 'data_year' => '',
            'methodology' => '', 'tools_used' => '', 'findings' => '', 'limitations' => '',
            'study_area' => '', 'cover_image' => null,
            'latitude' => null, 'longitude' => null, 'zoom' => 8,
            'tags' => '', 'status' => 'draft', 'featured' => 0,
            'published_at' => null, 'created_at' => null, 'updated_at' => null,
        ];
    }

    /**
     * @param array<string,mixed> $in
     * @return array<string,mixed>
     */
    private function collectFormData(array $in): array
    {
        $data = [
            'category_id'        => (int) ($in['category_id'] ?? 0),
            'title'              => $this->cleanStr($in['title']        ?? '', 200),
            'slug'               => $this->cleanStr($in['slug']         ?? '', 200),
            'summary'            => $this->cleanStr($in['summary']      ?? '', 400),
            'description'        => $this->cleanHtml($in['description'] ?? ''),
            'objectives'         => $this->cleanHtmlOrNull($in['objectives']         ?? ''),
            'research_questions' => $this->cleanStrOrNull($in['research_questions']  ?? '', 4000),
            'data_sources'       => $this->cleanStrOrNull($in['data_sources']        ?? '', 2000),
            'data_year'          => $this->cleanStrOrNull($in['data_year']           ?? '', 60),
            'methodology'        => $this->cleanHtmlOrNull($in['methodology']        ?? ''),
            'tools_used'         => $this->cleanStrOrNull($in['tools_used']          ?? '', 255),
            'findings'           => $this->cleanHtmlOrNull($in['findings']           ?? ''),
            'limitations'        => $this->cleanHtmlOrNull($in['limitations']        ?? ''),
            'study_area'         => $this->cleanStrOrNull($in['study_area']          ?? '', 150),
            'latitude'           => $this->cleanCoord($in['latitude']  ?? null),
            'longitude'          => $this->cleanCoord($in['longitude'] ?? null),
            'zoom'               => max(1, min(19, (int) ($in['zoom'] ?? 8))),
            'tags'               => $this->cleanStrOrNull($in['tags']                ?? '', 255),
            'status'             => in_array($in['status'] ?? '', ['draft','published'], true) ? (string) $in['status'] : 'draft',
            'featured'           => !empty($in['featured']) ? 1 : 0,
        ];
        if ($data['slug'] === '') {
            $data['slug'] = Slug::make($data['title'], isset($in['_id']) ? (int) $in['_id'] : null);
        }
        if ($data['status'] === 'published' && empty($in['_existing_published_at'])) {
            $data['published_at'] = gmdate('Y-m-d H:i:s');
        } else {
            $data['published_at'] = $in['published_at'] ?? null;
        }
        return $data;
    }

    /** @return array<string,string> */
    private function validate(array $data, ?int $ignoreProjectId): array
    {
        $errors = [];
        if ($data['category_id'] <= 0 || Category::find($data['category_id']) === null) {
            $errors['category_id'] = 'Please choose a category.';
        }
        if ($data['title'] === '') {
            $errors['title'] = 'Please enter a title.';
        }
        if ($data['summary'] === '') {
            $errors['summary'] = 'Please enter a short summary.';
        } elseif (mb_strlen($data['summary']) > 400) {
            $errors['summary'] = 'Summary must be 400 characters or fewer.';
        }
        if (!Slug::isValid($data['slug'])) {
            $errors['slug'] = 'Slug must contain only lowercase letters, digits, and hyphens.';
        } else {
            $stmt = \App\Services\Db::pdo()->prepare(
                'SELECT COUNT(*) FROM projects WHERE slug = :s'
                . ($ignoreProjectId !== null ? ' AND id <> :id' : '')
            );
            $params = [':s' => $data['slug']];
            if ($ignoreProjectId !== null) $params[':id'] = $ignoreProjectId;
            $stmt->execute($params);
            if ((int) $stmt->fetchColumn() > 0) {
                $errors['slug'] = 'That slug is already used by another project.';
            }
        }
        if ($data['latitude'] !== null && ($data['latitude'] < -90 || $data['latitude'] > 90)) {
            $errors['latitude'] = 'Latitude must be between -90 and 90.';
        }
        if ($data['longitude'] !== null && ($data['longitude'] < -180 || $data['longitude'] > 180)) {
            $errors['longitude'] = 'Longitude must be between -180 and 180.';
        }
        return $errors;
    }

    // ============================================================
    // HELPERS — string/HTML cleaners
    // ============================================================

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

    private function cleanHtml(mixed $v): string
    {
        if (!is_string($v)) return '';
        return \App\Services\HtmlSanitizer::sanitize($v);
    }

    private function cleanHtmlOrNull(mixed $v): ?string
    {
        $s = $this->cleanHtml($v);
        $plain = trim(strip_tags($s));
        return $plain === '' ? null : $s;
    }

    private function cleanCoord(mixed $v): ?string
    {
        if ($v === null || $v === '') return null;
        if (!is_numeric($v)) return null;
        $f = (float) $v;
        if ($f < -180 || $f > 180) return null;
        return number_format($f, 6, '.', '');
    }

    // ============================================================
    // HELPERS — request guards
    // ============================================================

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
            header('Location: ' . url('/admin/projects'), true, 303);
            exit;
        }
    }

    private function notFound(string $message): void
    {
        http_response_code(404);
        $title = 'Not Found — Admin';
        ob_start();
        ?>
        <section class="admin-page">
            <div class="admin-page__head">
                <p class="eyebrow">404</p>
                <h1><?= e($message) ?></h1>
                <p><a class="btn btn--primary" href="<?= e(url('/admin/projects')) ?>">Back to Projects</a></p>
            </div>
        </section>
        <?php
        $content = ob_get_clean();
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        require dirname(__DIR__) . '/Views/layouts/admin.php';
    }
}