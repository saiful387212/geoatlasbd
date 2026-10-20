<?php
declare(strict_types=1);

use App\Services\HtmlSanitizer;

/** @var array $project */
/** @var array $images */
/** @var array $files */
/** @var array $related */

$catColor   = $project['category_color'] ?: '#14B8A6';
$pubDate    = fmt_date($project['published_at'] ?? $project['created_at']);
$updated    = fmt_date($project['updated_at'] ?? null);
$tags       = split_tags($project['tags'] ?? null);
$sources    = parse_data_sources($project['data_sources'] ?? null);
$tools      = parse_data_sources($project['tools_used'] ?? null);
$questions  = parse_lines($project['research_questions'] ?? null);

$hasMap     = ($project['latitude'] !== null && $project['longitude'] !== null);
$coverPath  = safe_upload_path($project['cover_image'] ?? null, 'images');

// Sanitize every HTML field once.
$description = HtmlSanitizer::sanitize($project['description'] ?? '');
$objectives  = HtmlSanitizer::sanitize($project['objectives'] ?? '');
$methodology = HtmlSanitizer::sanitize($project['methodology'] ?? '');
$findings    = HtmlSanitizer::sanitize($project['findings'] ?? '');
$limitations = HtmlSanitizer::sanitize($project['limitations'] ?? '');

// Only show the first PDF as the primary download; others appear in the list.
$primaryPdf = null;
$extraFiles = [];
foreach ($files as $idx => $f) {
    $safe = safe_upload_path($f['file_path'], 'pdfs');
    if ($safe === null) continue;
    $f['_safe'] = $safe;
    if ($primaryPdf === null) {
        $primaryPdf = $f;
    } else {
        $extraFiles[] = $f;
    }
}
?>
<article class="project-detail">

    <!-- ============== HERO ============== -->
    <header class="page-hero project-detail__hero">
        <div class="container">
            <nav class="crumbs" aria-label="Breadcrumb">
                <ol>
                    <li><a href="<?= e(url('/')) ?>">Home</a></li>
                    <li><a href="<?= e(url('/projects')) ?>">Projects</a></li>
                    <li><a href="<?= e(url('/projects?category=' . urlencode($project['category_slug']))) ?>"><?= e($project['category_name']) ?></a></li>
                    <li aria-current="page"><?= e($project['title']) ?></li>
                </ol>
            </nav>

            <p class="badge" style="--badge-color: <?= e($catColor) ?>">
                <a class="badge__link"
                   href="<?= e(url('/projects?category=' . urlencode($project['category_slug']))) ?>">
                    <?= e($project['category_name']) ?>
                </a>
            </p>

            <h1 class="page-hero__title"><?= e($project['title']) ?></h1>
            <p class="page-hero__lead"><?= e($project['summary']) ?></p>

            <ul class="detail-meta" aria-label="Project information">
                <?php if (!empty($project['study_area'])): ?>
                    <li><span class="meta-label">Study area</span><?= e($project['study_area']) ?></li>
                <?php endif; ?>
                <?php if (!empty($project['data_year'])): ?>
                    <li><span class="meta-label">Data years</span><?= e($project['data_year']) ?></li>
                <?php endif; ?>
                <?php if ($pubDate !== ''): ?>
                    <li>
                        <span class="meta-label">Published</span>
                        <time datetime="<?= e(substr($project['published_at'] ?? $project['created_at'], 0, 10)) ?>">
                            <?= e($pubDate) ?>
                        </time>
                    </li>
                <?php endif; ?>
                <?php if ($updated !== '' && $updated !== $pubDate): ?>
                    <li><span class="meta-label">Updated</span><?= e($updated) ?></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <div class="container project-detail__layout">

        <!-- ============== MAIN COLUMN ============== -->
        <div class="project-detail__main">

            <?php if ($coverPath !== null): ?>
                <figure class="project-cover">
                    <img src="<?= e(upload_url($coverPath)) ?>"
                         alt="<?= e($project['title']) ?> — cover image"
                         loading="eager" decoding="async">
                </figure>
            <?php endif; ?>

            <?php if (trim($description) !== ''): ?>
                <?php
                $sectionTitle = 'Overview';
                $sectionHtml  = $description;
                $sectionId    = 'overview';
                require __DIR__ . '/partials/case-section.php';
                ?>
            <?php endif; ?>

            <?php if (!empty($project['study_area'])): ?>
                <?php
                $sectionTitle = 'Study area &amp; context';
                $sectionHtml  = '<p>' . e($project['study_area']) . '</p>';
                $sectionId    = 'study-area';
                require __DIR__ . '/partials/case-section.php';
                ?>
            <?php endif; ?>

            <?php if ($objectives !== '' || $questions !== []): ?>
                <section class="case-section" aria-labelledby="objectives">
                    <h2 id="objectives">Objectives &amp; research questions</h2>
                    <?php if ($objectives !== ''): ?>
                        <div class="prose"><?= $objectives ?></div>
                    <?php endif; ?>
                    <?php if ($questions !== []): ?>
                        <div class="case-subsection">
                            <h3 class="case-subsection__title">Research questions</h3>
                            <ol class="case-list">
                                <?php foreach ($questions as $qn): ?>
                                    <li><?= e($qn) ?></li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($sources !== [] || !empty($project['data_year'])): ?>
                <section class="case-section" aria-labelledby="data">
                    <h2 id="data">Data sources &amp; year</h2>
                    <dl class="data-grid">
                        <?php if (!empty($project['data_year'])): ?>
                            <div class="data-grid__item">
                                <dt>Data year(s)</dt>
                                <dd><?= e($project['data_year']) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if ($sources !== []): ?>
                            <div class="data-grid__item data-grid__item--wide">
                                <dt>Sources</dt>
                                <dd>
                                    <ul class="inline-list">
                                        <?php foreach ($sources as $s): ?>
                                            <li><?= e($s) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                </section>
            <?php endif; ?>

            <?php if ($methodology !== ''): ?>
                <?php
                $sectionTitle = 'Methodology &amp; workflow';
                $sectionHtml  = $methodology;
                $sectionId    = 'methodology';
                require __DIR__ . '/partials/case-section.php';
                ?>
            <?php endif; ?>

            <?php if ($tools !== []): ?>
                <section class="case-section" aria-labelledby="tools">
                    <h2 id="tools">Tools used</h2>
                    <ul class="tag-list" role="list">
                        <?php foreach ($tools as $t): ?>
                            <li><span class="filter-pill filter-pill--static"><?= e($t) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if ($hasMap): ?>
                <section class="project-map-wrap" aria-labelledby="map-heading">
                    <h2 id="map-heading" class="visually-hidden">Interactive map</h2>
                    <div class="project-map"
                         data-lat="<?= e((string) $project['latitude']) ?>"
                         data-lng="<?= e((string) $project['longitude']) ?>"
                         data-zoom="<?= (int) ($project['zoom'] ?: 8) ?>"
                         data-title="<?= e($project['title']) ?>"
                         role="region"
                         aria-label="Interactive map of the study area"></div>
                    <p class="project-map__hint">
                        Map data © <a href="https://www.openstreetmap.org/copyright" rel="noopener noreferrer" target="_blank">OpenStreetMap</a> contributors.
                    </p>
                </section>
            <?php endif; ?>

            <?php if ($images !== []): ?>
                <section class="project-gallery" aria-labelledby="gallery-h2">
                    <h2 id="gallery-h2">Image gallery</h2>
                    <ul class="project-gallery__grid" role="list">
                        <?php foreach ($images as $img):
                            $safeImg = safe_upload_path($img['file_path'], 'images');
                            if ($safeImg === null) continue;
                        ?>
                            <li>
                                <figure class="project-gallery__fig">
                                    <img src="<?= e(upload_url($safeImg)) ?>"
                                         alt="<?= e($img['alt_text'] ?: $project['title'] . ' image') ?>"
                                         loading="lazy" decoding="async"
                                         data-lightbox="project-gallery"
                                         data-title="<?= e($img['alt_text'] ?: $project['title']) ?>"
                                         role="button" tabindex="0"
                                         aria-haspopup="dialog"
                                         aria-label="View full-size image: <?= e($img['alt_text'] ?: $project['title']) ?>">
                                </figure>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if ($findings !== ''): ?>
                <?php
                $sectionTitle = 'Main findings';
                $sectionHtml  = $findings;
                $sectionId    = 'findings';
                require __DIR__ . '/partials/case-section.php';
                ?>
            <?php endif; ?>

            <?php if ($limitations !== ''): ?>
                <?php
                $sectionTitle = 'Limitations';
                $sectionHtml  = $limitations;
                $sectionId    = 'limitations';
                require __DIR__ . '/partials/case-section.php';
                ?>
            <?php endif; ?>

            <?php if ($tags !== []): ?>
                <section class="project-tags" aria-labelledby="tags-h2">
                    <h2 id="tags-h2" class="case-section__h-sm">Tags</h2>
                    <ul class="tag-list" role="list">
                        <?php foreach ($tags as $tag): ?>
                            <li>
                                <a class="filter-pill" href="<?= e(url('/projects?q=' . urlencode($tag))) ?>">
                                    <?= e($tag) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </div>

        <!-- ============== SIDEBAR ============== -->
        <aside class="project-detail__aside" aria-label="Project actions and files">
            <?php if ($primaryPdf !== null): ?>
                <a class="btn btn--primary btn--block"
                   href="<?= e(upload_url($primaryPdf['_safe'])) ?>"
                   download="<?= e($primaryPdf['original_name']) ?>">
                    Download report (PDF)
                </a>
            <?php endif; ?>

            <?php if ($extraFiles !== []): ?>
                <div class="aside-block">
                    <h2 class="aside-block__title">Attachments</h2>
                    <ul class="download-list" role="list">
                        <?php foreach ($extraFiles as $f): ?>
                            <li class="download-item">
                                <a href="<?= e(upload_url($f['_safe'])) ?>"
                                   download="<?= e($f['original_name']) ?>">
                                    <span class="download-item__icon" aria-hidden="true">PDF</span>
                                    <span class="download-item__name"><?= e($f['original_name']) ?></span>
                                    <?php if ($f['size_bytes'] > 0): ?>
                                        <span class="download-item__size"><?= e(number_format($f['size_bytes'] / 1024, 0)) ?> KB</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <a class="btn btn--ghost btn--block" href="<?= e(url('/projects')) ?>">← All projects</a>
            <a class="btn btn--ghost btn--block" href="<?= e(url('/contact')) ?>">Discuss this project</a>

            <div class="aside-block">
                <h2 class="aside-block__title">Category</h2>
                <p>
                    <a class="chip chip--inline"
                       href="<?= e(url('/projects?category=' . urlencode($project['category_slug']))) ?>"
                       style="--chip-color: <?= e($catColor) ?>">
                        <?= e($project['category_name']) ?>
                    </a>
                </p>
            </div>

            <?php if ($pubDate !== ''): ?>
                <div class="aside-block">
                    <h2 class="aside-block__title">Published</h2>
                    <p><?= e($pubDate) ?></p>
                </div>
            <?php endif; ?>
        </aside>
    </div>

    <?php if ($related !== []): ?>
        <section class="section section--surface" aria-labelledby="related-h2">
            <div class="container">
                <header class="section__head">
                    <p class="eyebrow">Continue reading</p>
                    <h2 id="related-h2" class="section__title">Related projects</h2>
                </header>
                <div class="card-grid">
                    <?php foreach ($related as $p): ?>
                        <?php require __DIR__ . '/partials/project-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</article>