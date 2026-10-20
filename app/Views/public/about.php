<?php
declare(strict_types=1);
/** @var array $profile */

// Build the contact link set, ignoring nulls.
$links = [];
if (!empty($profile['links']['linkedin'])) $links[] = ['label' => 'LinkedIn', 'href' => $profile['links']['linkedin'], 'external' => true];
if (!empty($profile['links']['github']))   $links[] = ['label' => 'GitHub',   'href' => $profile['links']['github'],   'external' => true];
if (!empty($profile['links']['email']))    $links[] = ['label' => 'Email',    'href' => 'mailto:' . $profile['links']['email'], 'external' => false];
if (!empty($profile['links']['cv']))       $links[] = ['label' => 'Download CV', 'href' => $profile['links']['cv'], 'external' => false];
?>

<section class="page-hero">
    <div class="container">
        <p class="eyebrow">About</p>
        <h1 class="page-hero__title"><?= e($profile['name']) ?></h1>
        <p class="page-hero__lead"><?= e($profile['role']) ?> — <?= e($profile['location']) ?></p>
        <p class="page-hero__lead"><?= e($profile['bio_short']) ?></p>

        <?php if ($links !== []): ?>
            <ul class="about-links" role="list">
                <?php foreach ($links as $l):
                    $href = safe_href($l['href']);
                    if ($href === null) continue;
                ?>
                    <li>
                        <a class="btn btn--ghost"
                           href="<?= e($href) ?>"
                           <?= $l['external'] ? 'rel="noopener noreferrer" target="_blank"' : '' ?>>
                            <?= e($l['label']) ?>
                            <?php if ($l['external']): ?>
                                <span aria-hidden="true">↗</span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="about-links-hint">
                <em>Links to LinkedIn, GitHub, email, and CV will appear here once configured.</em>
            </p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container about-grid">

        <div class="about-main">
            <section class="case-section">
                <h2>Biography</h2>
                <div class="prose">
                    <?php foreach ($profile['bio_long'] as $p): ?>
                        <p><?= e($p) ?></p>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="case-section">
                <h2>Education</h2>
                <?php if ($profile['education'] === []): ?>
                    <p class="muted">Education details will be added soon.</p>
                <?php else: ?>
                    <ul class="timeline" role="list">
                        <?php foreach ($profile['education'] as $ed): ?>
                            <li class="timeline__item">
                                <p class="timeline__year"><?= e($ed['year']) ?></p>
                                <h3 class="timeline__title"><?= e($ed['degree']) ?></h3>
                                <p class="timeline__meta"><?= e($ed['institution']) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <section class="case-section">
                <h2>Research interests</h2>
                <ul class="tag-list" role="list">
                    <?php foreach ($profile['interests'] as $interest): ?>
                        <li><span class="filter-pill filter-pill--static"><?= e($interest) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="case-section">
                <h2>Experience</h2>
                <?php if ($profile['experience'] === []): ?>
                    <p class="muted">
                        No professional experience listed yet. This section is intentionally blank
                        until there is real work to describe.
                    </p>
                <?php else: ?>
                    <ul class="timeline" role="list">
                        <?php foreach ($profile['experience'] as $x): ?>
                            <li class="timeline__item">
                                <p class="timeline__year"><?= e($x['year']) ?></p>
                                <h3 class="timeline__title"><?= e($x['role']) ?></h3>
                                <p class="timeline__meta"><?= e($x['org']) ?></p>
                                <p><?= e($x['summary']) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>

        <aside class="about-aside" aria-label="Skills and focus areas">
            <div class="aside-block">
                <h2 class="aside-block__title">GIS &amp; analytical skills</h2>
                <?php foreach ($profile['skills'] as $group => $items): ?>
                    <div class="skill-group">
                        <h3 class="skill-group__title"><?= e($group) ?></h3>
                        <ul class="inline-list" role="list">
                            <?php foreach ($items as $item): ?>
                                <li><?= e($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="aside-block">
                <h2 class="aside-block__title">Focus areas</h2>
                <p>
                    GIS &middot; Remote sensing &middot; Cartography &middot;
                    Spatial analysis &middot; Geographical research.
                </p>
            </div>

            <div class="aside-block">
                <h2 class="aside-block__title">Get in touch</h2>
                <p>Questions, collaborations, or feedback are welcome.</p>
                <p><a class="btn btn--primary btn--block" href="<?= e(url('/contact')) ?>">Open contact page</a></p>
            </div>
        </aside>
    </div>
</section>