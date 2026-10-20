<?php
declare(strict_types=1);
/** @var array $user */
/** @var array $stats */
?>
<section class="admin-page">
    <div class="admin-page__head">
        <p class="eyebrow">Dashboard</p>
        <h1>Welcome, <?= e($user['name']) ?></h1>
        <p class="muted">
            Signed in as <strong><?= e($user['email']) ?></strong>.
            Manage projects, categories, and incoming contact messages.
        </p>
    </div>

    <div class="admin-stats">
        <div class="admin-stat">
            <p class="admin-stat__label">Projects</p>
            <p class="admin-stat__value"><?= (int) $stats['projects'] ?></p>
        </div>
        <div class="admin-stat">
            <p class="admin-stat__label">Published</p>
            <p class="admin-stat__value"><?= (int) $stats['published'] ?></p>
        </div>
        <div class="admin-stat">
            <p class="admin-stat__label">Drafts</p>
            <p class="admin-stat__value"><?= (int) $stats['drafts'] ?></p>
        </div>
        <div class="admin-stat">
            <p class="admin-stat__label">Categories</p>
            <p class="admin-stat__value"><?= (int) $stats['categories'] ?></p>
        </div>
        <div class="admin-stat admin-stat--accent">
            <p class="admin-stat__label">Unread messages</p>
            <p class="admin-stat__value"><?= (int) $stats['unread_messages'] ?></p>
        </div>
    </div>

    <div class="admin-section-links">
        <a class="admin-panel" href="<?= e(url('/admin/projects')) ?>"><h2>Manage projects</h2><p class="muted">Create, edit, publish, or remove map projects.</p></a>
        <a class="admin-panel" href="<?= e(url('/admin/categories')) ?>"><h2>Manage categories</h2><p class="muted">Organize the themes used by projects.</p></a>
        <a class="admin-panel" href="<?= e(url('/admin/messages')) ?>"><h2>Review messages</h2><p class="muted">Read, track, and remove contact submissions.</p></a>
    </div>
</section>