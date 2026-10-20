<?php
declare(strict_types=1);
use App\Services\Auth;
use App\Services\Csrf;

$currentUser = Auth::user();
$logoutCsrf  = $currentUser ? Csrf::token('admin_logout') : null;
?>
<header class="admin-header">
    <div class="admin-header__inner">
        <a class="admin-brand" href="<?= e(url($currentUser ? '/admin/dashboard' : '/admin/login')) ?>">
            <img src="<?= e(url('/assets/img/logo.svg')) ?>" alt="" width="28" height="28">
            <span>
                <span class="admin-brand__name">GeoAtlas Admin</span>
                <?php if ($currentUser): ?>
                    <span class="admin-brand__user"><?= e($currentUser['name']) ?> · <?= e($currentUser['role']) ?></span>
                <?php endif; ?>
            </span>
        </a>

        <?php if ($currentUser): ?>
            <nav class="admin-nav" aria-label="Admin">
                <a href="<?= e(url('/admin/dashboard')) ?>"
                   class="<?= nav_is_active($_SERVER['REQUEST_URI'] ?? '', '/admin/dashboard') ? 'is-active' : '' ?>">
                    Dashboard
                </a>
                <!-- CRUD links land in Phase 8:
                <a href="<?= e(url('/admin/projects')) ?>">Projects</a>
                <a href="<?= e(url('/admin/categories')) ?>">Categories</a>
                <a href="<?= e(url('/admin/messages')) ?>">Messages</a>
                -->
                <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">View site ↗</a>
            </nav>

            <form class="admin-logout" method="post" action="<?= e(url('/admin/logout')) ?>">
                <input type="hidden" name="_csrf" value="<?= e((string) $logoutCsrf) ?>">
                <button class="btn btn--ghost btn--sm" type="submit">Sign out</button>
            </form>
        <?php endif; ?>
    </div>
</header>