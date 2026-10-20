<?php
declare(strict_types=1);
if (defined('GEOATLAS_ADMIN_LAYOUT_RENDERED')) {
    return;
}
define('GEOATLAS_ADMIN_LAYOUT_RENDERED', true);

/** @var string $content */
/** @var string $title */
/** @var array  $config */
$title = $title ?? 'Admin — GeoAtlas Bangladesh';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?></title>
    <meta name="theme-color" content="#0B172A">
    <link rel="icon" type="image/svg+xml" href="<?= e(url('/assets/img/logo.png')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/admin.css')) ?>">
</head>
<body class="admin-body">
    <a class="skip-link" href="#admin-main">Skip to content</a>

    <?php require __DIR__ . '/../admin/partials/header.php'; ?>
    <?php require __DIR__ . '/../partials/flash.php'; ?>

    <main id="admin-main">
        <?= $content ?>
    </main>

    <?php require __DIR__ . '/../admin/partials/footer.php'; ?>
</body>
</html>