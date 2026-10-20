<?php
declare(strict_types=1);
/** @var array $result */
/** @var \App\Services\Paginator $paginator */
/** @var array $categories */
/** @var array $qs */
/** @var ?string $categorySlug */
/** @var ?string $q */
/** @var string $sort */
/** @var ?array $activeCategory */
/** @var ?string $dataError */

$rows  = $result['rows'];
$total = $paginator->total;
?>

<section class="page-hero page-hero--compact">
    <div class="container">
        <p class="eyebrow">GIS projects</p>
        <h1 class="page-hero__title">Project Gallery</h1>
        <p class="page-hero__lead">
            Every published GIS, cartography, and remote-sensing project in the atlas.
            Filter by theme, search by keyword, or browse by date.
        </p>
    </div>
</section>

<section class="section section--surface" aria-labelledby="gallery-heading">
    <div class="container">
        <h2 id="gallery-heading" class="visually-hidden">Project results</h2>

        <?php if ($dataError !== null): ?>
            <div class="flash flash--error" role="alert"><?= e($dataError) ?></div>
        <?php endif; ?>

        <?php require __DIR__ . '/partials/filters.php'; ?>

        <div class="gallery-summary" aria-live="polite">
            <?php if ($q !== null || $categorySlug !== null): ?>
                <p>
                    <strong><?= (int) $total ?></strong> matching project<?= $total === 1 ? '' : 's' ?>
                    <?php if ($activeCategory !== null): ?>
                        in <em><?= e($activeCategory['name']) ?></em>
                    <?php endif; ?>
                    <?php if ($q !== null): ?>
                        for “<em><?= e($q) ?></em>”
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <p><strong><?= (int) $total ?></strong> published project<?= $total === 1 ? '' : 's' ?></p>
            <?php endif; ?>
        </div>

        <?php if ($rows === []): ?>
            <div class="empty-state empty-state--lg" role="status">
                <h3 class="empty-state__title">No projects match those filters</h3>
                <p>
                    <?php if ($q !== null || $categorySlug !== null): ?>
                        Try clearing the search or choosing a different category.
                    <?php else: ?>
                        No published projects yet. New projects appear here as soon as they are published.
                    <?php endif; ?>
                </p>
                <?php if ($q !== null || $categorySlug !== null): ?>
                    <p><a class="btn btn--primary" href="<?= e(url('/projects')) ?>">Clear filters</a></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($rows as $p): ?>
                    <?php require __DIR__ . '/partials/project-card.php'; ?>
                <?php endforeach; ?>
            </div>

            <?php require __DIR__ . '/partials/pagination.php'; ?>
        <?php endif; ?>
    </div>
</section>