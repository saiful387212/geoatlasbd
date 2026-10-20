<?php
declare(strict_types=1);
/** @var array $p */
$catColor  = $p['category_color'] ?: '#14B8A6';
$coverSafe = safe_upload_path($p['cover_image'] ?? null, 'images');
$hasCover  = $coverSafe !== null;
$dateStr   = fmt_date($p['published_at'] ?? $p['created_at'] ?? null);
?>
<article class="project-card">
    <a class="project-card__media-link" href="<?= e(url('/projects/' . $p['slug'])) ?>"
       aria-label="<?= e($p['title']) ?>">
        <div class="project-card__media" aria-hidden="true">
            <?php if ($hasCover): ?>
                <img src="<?= e(upload_url($coverSafe)) ?>"
                     alt="" loading="lazy" decoding="async" width="480" height="300">
            <?php else: ?>
                <div class="project-card__placeholder" style="--cat-color: <?= e($catColor) ?>">
                    <span>Map preview</span>
                </div>
            <?php endif; ?>
        </div>
    </a>

    <div class="project-card__body">
        <p class="badge" style="--badge-color: <?= e($catColor) ?>">
            <a href="<?= e(url('/projects?category=' . urlencode($p['category_slug']))) ?>"
               class="badge__link"><?= e($p['category_name']) ?></a>
        </p>

        <h3 class="project-card__title">
            <a href="<?= e(url('/projects/' . $p['slug'])) ?>"><?= e($p['title']) ?></a>
        </h3>

        <p class="project-card__summary"><?= e(truncate($p['summary'], 160)) ?></p>

        <ul class="project-card__meta" aria-label="Project details">
            <?php if (!empty($p['study_area'])): ?>
                <li><span class="meta-label">Study area</span><?= e($p['study_area']) ?></li>
            <?php endif; ?>
            <?php if ($dateStr !== ''): ?>
                <li><span class="meta-label">Published</span>
                    <time datetime="<?= e(substr($p['published_at'] ?? $p['created_at'], 0, 10)) ?>">
                        <?= e($dateStr) ?>
                    </time>
                </li>
            <?php endif; ?>
        </ul>

        <p class="project-card__cta">
            <a class="link-arrow" href="<?= e(url('/projects/' . $p['slug'])) ?>">
                View project<span class="visually-hidden">: <?= e($p['title']) ?></span>
            </a>
        </p>
    </div>
</article>