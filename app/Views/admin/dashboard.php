<?php
declare(strict_types=1);
/** @var array $user */
/** @var array $stats */
/** @var array $recent */
?>
<section class="admin-page">
    <div class="admin-page__head">
        <p class="eyebrow">Dashboard</p>
        <h1>Welcome, <?= e($user['name']) ?></h1>
        <p class="muted">Signed in as <strong><?= e($user['email']) ?></strong>.</p>
    </div>

    <div class="admin-stats">
        <a class="admin-stat" href="<?= e(url('/admin/projects')) ?>">
            <p class="admin-stat__label">Total projects</p>
            <p class="admin-stat__value"><?= (int) $stats['projects'] ?></p>
        </a>
        <a class="admin-stat" href="<?= e(url('/admin/projects?status=published')) ?>">
            <p class="admin-stat__label">Published</p>
            <p class="admin-stat__value"><?= (int) $stats['published'] ?></p>
        </a>
        <a class="admin-stat" href="<?= e(url('/admin/projects?status=draft')) ?>">
            <p class="admin-stat__label">Drafts</p>
            <p class="admin-stat__value"><?= (int) $stats['drafts'] ?></p>
        </a>
        <a class="admin-stat" href="<?= e(url('/admin/categories')) ?>">
            <p class="admin-stat__label">Categories</p>
            <p class="admin-stat__value"><?= (int) $stats['categories'] ?></p>
        </a>
        <a class="admin-stat admin-stat--accent" href="<?= e(url('/admin/projects?featured=1')) ?>">
            <p class="admin-stat__label">Featured (published)</p>
            <p class="admin-stat__value"><?= (int) $stats['featured'] ?></p>
        </a>
    </div>

    <div class="admin-actions">
        <a class="btn btn--primary" href="<?= e(url('/admin/projects/create')) ?>">+ New project</a>
        <a class="btn btn--ghost" href="<?= e(url('/admin/categories/create')) ?>">+ New category</a>
        <a class="btn btn--ghost" href="<?= e(url('/admin/projects')) ?>">Manage projects</a>
    </div>

    <?php if ($recent !== []): ?>
        <section class="admin-panel">
            <h2 class="admin-panel__title">Recently updated</h2>
            <table class="admin-table">
                <thead><tr><th>Title</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($recent as $p): ?>
                    <tr>
                        <td><?= e($p['title']) ?></td>
                        <td><span class="status-pill status-pill--<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                        <td><?= e(fmt_date($p['updated_at'], 'M j, Y H:i')) ?></td>
                        <td><a href="<?= e(url('/admin/projects/' . (int) $p['id'] . '/edit')) ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</section>