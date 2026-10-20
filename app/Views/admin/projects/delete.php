<?php
declare(strict_types=1);
/** @var array $project */
/** @var string $csrf */
use App\Services\Uploader;

$images = \App\Models\ProjectImage::forProject((int) $project['id']);
$files  = \App\Models\ProjectFile::forProject((int) $project['id']);
?>
<section class="admin-page">
    <div class="admin-page__head">
        <p class="eyebrow">Confirm delete</p>
        <h1>Delete “<?= e($project['title']) ?>”?</h1>
        <p>This action cannot be undone. The project, its gallery images, and its PDF report will be removed.</p>
    </div>

    <div class="admin-panel">
        <dl class="confirm-summary">
            <div><dt>Slug</dt><dd><?= e($project['slug']) ?></dd></div>
            <div><dt>Status</dt><dd><?= e($project['status']) ?></dd></div>
            <div><dt>Gallery images</dt><dd><?= count($images) ?></dd></div>
            <div><dt>PDF files</dt><dd><?= count($files) ?></dd></div>
        </dl>
    </div>

    <form method="post" action="<?= e(url('/admin/projects/' . (int) $project['id'] . '/delete')) ?>" class="admin-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <p class="admin-form__actions">
            <button class="btn btn--danger" type="submit">Yes, delete permanently</button>
            <a class="btn btn--ghost" href="<?= e(url('/admin/projects')) ?>">Cancel</a>
        </p>
    </form>
</section>