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
            Project, category, and message management arrive in Phase 8.
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

    <div class="admin-notice">
        <h2 class="admin-notice__title">Next steps</h2>
        <ul class="admin-checklist">
            <li>Change your password on first login (Phase 8 will add the form).</li>
            <li>Confirm your email address is unique and correct.</li>
            <li>Keep SMTP credentials out of <code>.env</code> until email is configured.</li>
        </ul>
    </div>
</section>