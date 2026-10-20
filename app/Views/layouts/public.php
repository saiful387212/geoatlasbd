<?php
declare(strict_types=1);
if (defined('GEOATLAS_PUBLIC_LAYOUT_RENDERED')) {
    return;
}
define('GEOATLAS_PUBLIC_LAYOUT_RENDERED', true);

/** @var string $content */
/** @var string $title */
/** @var string $metaDescription */
/** @var array  $config */
$title           = $title           ?? 'GeoAtlas Bangladesh';
$metaDescription = $metaDescription ?? 'Understanding Bangladesh Through Maps.';
$currentPath     = $_SERVER['REQUEST_URI'] ?? '/';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="theme-color" content="#0B172A">

    <link rel="icon" type="image/svg+xml" href="<?= e(url('/assets/img/logo.png')) ?>">
    <link rel="preload" href="<?= e(url('/assets/css/main.css')) ?>" as="style">
    <link rel="stylesheet" href="<?= e(url('/assets/css/main.css')) ?>">
    <script src="<?= e(url('/assets/js/main.js')) ?>" defer></script>
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>

    <?php require dirname(__DIR__) . '/partials/header.php'; ?>
    <?php require dirname(__DIR__) . '/partials/flash.php'; ?>

    <main id="main">
        <?= $content /* trusted: rendered by controllers from PHP templates */ ?>
    </main>

    <?php require dirname(__DIR__) . '/partials/footer.php'; ?>
    <?php if (!empty($needsLeaflet)): ?>
    <link rel="stylesheet" href="<?= e(url('/assets/vendor/leaflet/leaflet.css')) ?>">
    <script src="<?= e(url('/assets/vendor/leaflet/leaflet.js')) ?>" defer></script>
    <script src="<?= e(url('/assets/js/project-map.js')) ?>" defer></script>
<?php endif; ?>
<?php if (!empty($needsContactScript)): ?>
    <script src="<?= e(url('/assets/js/contact.js')) ?>" defer></script>
<?php endif; ?>
<!-- Lightbox Overlay -->
<div id="lightbox" class="lightbox" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Image viewer">
    <button class="lightbox__close" aria-label="Close image viewer">&times;</button>
    <div class="lightbox__content">
        <img id="lightbox-img" src="" alt="">
        <p id="lightbox-title" class="lightbox__title"></p>
    </div>
</div>
</body>
</html>