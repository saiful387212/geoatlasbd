<?php
declare(strict_types=1);
/** @var array $profile */

// Build the contact link set, ignoring nulls.
$links = [];
if (!empty($profile['links']['linkedin'])) $links[] = ['label' => 'LinkedIn', 'href' => $profile['links']['linkedin'], 'external' => true];
if (!empty($profile['links']['github']))   $links[] = ['label' => 'GitHub',   'href' => $profile['links']['github'],   'external' => true];
if (!empty($profile['links']['email']))    $links[] = ['label' => 'Email',    'href' => 'mailto:' . $profile['links']['email'], 'external' => false];
if (!empty($profile['links']['cv']))       $links[] = ['label' => 'Download CV (PDF)', 'href' => $profile['links']['cv'], 'external' => false];
?>

<section class="page-hero">
    <div class="container">
        <p class="eyebrow">About Me</p>
        <h1 class="page-hero__title"><?= e($profile['name']) ?></h1>
        <p class="page-hero__lead"><?= e($profile['role']) ?> — <?= e($profile['location']) ?></p>
        <p class="page-hero__lead" style="margin-top: 1rem; max-width: 800px;"><?= e($profile['bio_short']) ?></p>

        <?php if ($links !== []): ?>
            <ul class="about-links" role="list" style="margin-top: 1.5rem;">
                <?php foreach ($links as $l):
                    $href = safe_href($l['href']);
                    if ($href === null) continue;
                ?>
                    <li>
                        <a class="btn <?= $l['label'] === 'Download CV (PDF)' ? 'btn--primary' : 'btn--ghost' ?>"
                           href="<?= e($href) ?>"
                           <?= $l['external'] ? 'rel="noopener noreferrer" target="_blank"' : '' ?>>
                            <?= e($l['label']) ?>
                            <?php if ($l['external']): ?><span aria-hidden="true">↗</span><?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container about-grid">

        <div class="about-main">
            <section class="case-section">
                <h2>Career Objective</h2>
                <div class="prose">
                    <?php foreach ($profile['bio_long'] as $p): ?>
                        <p><?= e($p) ?></p>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="case-section">
                <h2>Education</h2>
                <ul class="timeline" role="list">
                    <?php foreach ($profile['education'] as $ed): ?>
                        <li class="timeline__item">
                            <p class="timeline__year"><?= e($ed['year']) ?></p>
                            <h3 class="timeline__title"><?= e($ed['degree']) ?></h3>
                            <p class="timeline__meta"><?= e($ed['institution']) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="case-section">
                <h2>Achievements & Training</h2>
                <ul class="case-list" style="list-style-type: disc; padding-left: 1.5rem;">
                    <?php foreach ($profile['achievements'] as $achievement): ?>
                        <li style="display: list-item; background: transparent; border: none; padding: 0.5rem 0; color: #CBD5E1;">
                            <?= e($achievement) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="case-section">
                <h2>Leadership & Experience</h2>
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
            </section>
        </div>

        <aside class="about-aside" aria-label="Skills and focus areas">
            <div class="aside-block">
                <h2 class="aside-block__title">Technical & Soft Skills</h2>
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
                <h2 class="aside-block__title">Research Interests</h2>
                <ul class="tag-list" role="list">
                    <?php foreach ($profile['interests'] as $interest): ?>
                        <li><span class="filter-pill filter-pill--static"><?= e($interest) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="aside-block">
                <h2 class="aside-block__title">Get in touch</h2>
                <p>Feel free to reach out for collaborations, research discussions, or just to say hello.</p>
                <p><a class="btn btn--primary btn--block" href="<?= e(url('/contact')) ?>">Open contact page</a></p>
            </div>
        </aside>
    </div>
</section>