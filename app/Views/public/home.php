<?php
declare(strict_types=1);
/** @var array $featured */
/** @var array $categories */
/** @var bool  $hasFeatured */
/** @var string|null $dataError */

// Compute a small map center if any featured project has coordinates.
$mapCenter = null;
foreach ($featured as $p) {
    if ($p['latitude'] !== null && $p['longitude'] !== null) {
        $mapCenter = [(float) $p['longitude'], (float) $p['latitude']];
        break;
    }
}
?>

<?php if ($dataError !== null): ?>
    <div class="container flash-stack" role="alert">
        <div class="flash flash--error"><?= e($dataError) ?></div>
    </div>
<?php endif; ?>

<!-- ================= HERO ================= -->
<section class="hero">
    <div class="container hero__inner">
        <div class="hero__copy">
            <p class="eyebrow">GIS &middot; Cartography &middot; Remote Sensing</p>
            <h1 class="hero__title">GeoAtlas Bangladesh</h1>
            <p class="hero__tagline">Understanding Bangladesh Through Maps.</p>

            <!-- PLACEHOLDER: replace with your real bio -->
            <p class="hero__lead">
                I explore the landscapes, rivers, cities, and communities of Bangladesh
                through geographic information systems, cartography, remote sensing,
                and spatial research. This atlas is a working portfolio of that practice.
            </p>

            <div class="hero__actions">
                <a class="btn btn--primary" href="<?= e(url('/projects')) ?>">Explore Projects</a>
                <a class="btn btn--ghost" href="<?= e(url('/about')) ?>">About the Work</a>
            </div>
        </div>

        <div class="hero__panel" aria-hidden="true">
            <div class="hero__map">
                <img class="hero__map-svg" src="<?= e(url('/assets/img/bd-outline.svg')) ?>" alt="">
                <span class="hero__pin hero__pin--dhaka"><span>Dhaka</span></span>
                <span class="hero__pin hero__pin--padma"><span>Padma</span></span>
                <span class="hero__pin hero__pin--sundarbans"><span>Sundarbans</span></span>
            </div>
            <div class="hero__panel-grid"></div>
        </div>
</section>

<!-- ================= FEATURED PROJECTS ================= -->
<section class="section section--surface" aria-labelledby="featured-heading">
    <div class="container">
        <header class="section__head">
            <p class="eyebrow"><?= $hasFeatured ? 'Featured' : 'Latest' ?> work</p>
            <h2 id="featured-heading" class="section__title">Selected GIS projects</h2>
            <p class="section__lead">
                <?php if ($hasFeatured): ?>
                    Hand-picked case studies from my recent mapping work.
                <?php else: ?>
                    The most recent published projects. Mark a project as featured in the admin dashboard to pin it here.
                <?php endif; ?>
            </p>
        </header>

        <?php if ($featured === []): ?>
            <div class="empty-state">
                <p>No published projects yet. Once you publish a project it will appear here.</p>
                <a class="btn btn--primary" href="<?= e(url('/projects')) ?>">Open gallery</a>
            </div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($featured as $p): ?>
                    <?php
                    $catColor = $p['category_color'] ?: '#14B8A6';
                    $hasCover = !empty($p['cover_image']);
                    ?>
                    <article class="project-card">
                        <div class="project-card__media" aria-hidden="<?= $hasCover ? 'false' : 'true' ?>">
                            <?php if ($hasCover): ?>
                                <img src="<?= e(upload_url($p['cover_image'])) ?>"
                                     alt="" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="project-card__placeholder" style="--cat-color: <?= e($catColor) ?>">
                                    <span>Map preview</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="project-card__body">
                            <p class="badge" style="--badge-color: <?= e($catColor) ?>">
                                <?= e($p['category_name']) ?>
                            </p>
                            <h3 class="project-card__title">
                                <a href="<?= e(url('/projects/' . $p['slug'])) ?>">
                                    <?= e($p['title']) ?>
                                </a>
                            </h3>
                            <p class="project-card__summary"><?= e($p['summary']) ?></p>
                            <p class="project-card__cta">
                                <a class="link-arrow" href="<?= e(url('/projects/' . $p['slug'])) ?>">
                                    View project
                                </a>
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ================= CATEGORIES ================= -->
<section class="section" aria-labelledby="categories-heading">
    <div class="container">
        <header class="section__head">
            <p class="eyebrow">Explore by theme</p>
            <h2 id="categories-heading" class="section__title">GIS categories</h2>
            <p class="section__lead">
                Themes I map across Bangladesh. Each category groups projects by subject and method.
            </p>
        </header>

        <?php if ($categories === []): ?>
            <div class="empty-state"><p>No categories yet.</p></div>
        <?php else: ?>
            <ul class="chip-grid" role="list">
                <?php foreach ($categories as $c): ?>
                    <?php $color = $c['color'] ?: '#14B8A6'; ?>
                    <li>
                        <a class="chip" href="<?= e(url('/projects?category=' . urlencode($c['slug']))) ?>"
                           style="--chip-color: <?= e($color) ?>">
                            <span class="chip__name"><?= e($c['name']) ?></span>
                            <span class="chip__count"><?= (int) $c['project_count'] ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<!-- ================= METHODS ================= -->
<section class="section section--surface" aria-labelledby="methods-heading">
    <div class="container">
        <header class="section__head">
            <p class="eyebrow">Tools &amp; methods</p>
            <h2 id="methods-heading" class="section__title">How these maps are made</h2>
            <p class="section__lead">
                <!-- PLACEHOLDER: edit this summary with your actual stack -->
                A reproducible workflow from source data to final cartography.
            </p>
        </header>

        <div class="method-grid">
            <article class="method-card">
                <h3 class="method-card__title">Spatial analysis</h3>
                <p>Vector and raster processing, overlay analysis, suitability modelling, and network analysis.</p>
                <p class="method-card__tags"><span>QGIS</span><span>GDAL</span><span>PostGIS</span></p>
            </article>
            <article class="method-card">
                <h3 class="method-card__title">Remote sensing</h3>
                <p>Land cover classification, NDVI time series, change detection, and urban heat analysis from satellite imagery.</p>
                <p class="method-card__tags"><span>Landsat</span><span>Sentinel</span><span>Google Earth Engine</span></p>
            </article>
            <article class="method-card">
                <h3 class="method-card__title">Cartography</h3>
                <p>Editorial map design, typography, projections, and thematic symbology tuned for both print and web.</p>
                <p class="method-card__tags"><span>QGIS</span><span>Inkscape</span><span>Leaflet</span></p>
            </article>
            <article class="method-card">
                <h3 class="method-card__title">Web GIS</h3>
                <p>Interactive map publishing with Leaflet, GeoJSON delivery, and open standards.</p>
                <p class="method-card__tags"><span>Leaflet</span><span>GeoJSON</span><span>OSM</span></p>
            </article>
        </div>
    </div>
</section>

<!-- ================= CTA ================= -->
<section class="cta">
    <div class="container cta__inner">
        <div>
            <h2 class="cta__title">Explore the full gallery</h2>
            <p class="cta__lead">Browse every GIS project, filter by theme, and open interactive maps.</p>
        </div>
        <a class="btn btn--primary btn--lg" href="<?= e(url('/projects')) ?>">Open Project Gallery</a>
    </div>
</section>