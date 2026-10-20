<?php
declare(strict_types=1);
/** @var string $currentPath */
?>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="GeoAtlas Bangladesh — home">
            <img class="brand__mark" src="<?= e(url('/assets/img/logo.png')) ?>" alt="" width="36" height="36">
            <span class="brand__text">
                <span class="brand__name">GeoAtlas Bangladesh</span>
                <span class="brand__tagline">Understanding Bangladesh Through Maps.</span>
            </span>
        </a>

        <button class="nav-toggle" type="button"
                aria-controls="primary-navigation" aria-expanded="false"
                data-nav-toggle>
            <span class="nav-toggle__bars" aria-hidden="true"></span>
            <span class="visually-hidden">Toggle navigation</span>
        </button>

        <?php require __DIR__ . '/nav.php'; ?>
    </div>
</header>