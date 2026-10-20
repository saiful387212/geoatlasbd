<?php
declare(strict_types=1);
/** @var array  $categories */
/** @var ?string $categorySlug */
/** @var ?string $q */
/** @var string $sort */
?>
<form class="filters" method="get" action="<?= e(url('/projects')) ?>" role="search" aria-label="Filter projects"
      data-gallery-filters>
    <div class="filters__row">
        <div class="filters__field filters__field--grow">
            <label class="visually-hidden" for="f-q">Search projects</label>
            <input id="f-q" type="search" name="q" value="<?= e($q ?? '') ?>"
                   placeholder="Search title, summary, tags, study area…" maxlength="100"
                   autocomplete="off">
        </div>

        <div class="filters__field">
            <label class="visually-hidden" for="f-category">Category</label>
            <select id="f-category" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= e($c['slug']) ?>"
                        <?= ($categorySlug === $c['slug']) ? 'selected' : '' ?>>
                        <?= e($c['name']) ?> (<?= (int) $c['project_count'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filters__field">
            <label class="visually-hidden" for="f-sort">Sort</label>
            <select id="f-sort" name="sort">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest first</option>
                <option value="title"  <?= $sort === 'title'  ? 'selected' : '' ?>>Title A–Z</option>
            </select>
        </div>

        <div class="filters__actions">
            <button class="btn btn--primary" type="submit">Apply</button>
            <?php if ($q !== null || $categorySlug !== null || $sort !== 'newest'): ?>
                <a class="btn btn--ghost" href="<?= e(url('/projects')) ?>">Reset</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($q !== null || $categorySlug !== null): ?>
        <p class="filters__active" aria-live="polite">
            <span class="filters__active-label">Active filters:</span>
            <?php if ($categorySlug !== null): ?>
                <a class="filter-pill" href="<?= e(url('/projects?' . http_build_query(array_filter(['q' => $q, 'sort' => $sort === 'newest' ? null : $sort])))) ?>">
                    Category: <?= e($categorySlug) ?> <span aria-hidden="true">×</span>
                </a>
            <?php endif; ?>
            <?php if ($q !== null): ?>
                <a class="filter-pill" href="<?= e(url('/projects?' . http_build_query(array_filter(['category' => $categorySlug, 'sort' => $sort === 'newest' ? null : $sort])))) ?>">
                    “<?= e($q) ?>” <span aria-hidden="true">×</span>
                </a>
            <?php endif; ?>
        </p>
    <?php endif; ?>
</form>