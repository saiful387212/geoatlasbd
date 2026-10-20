<?php
declare(strict_types=1);
/** @var array $result */
/** @var \App\Services\Paginator $paginator */
/** @var array $categories */
/** @var array $qs */
/** @var ?string $q */
/** @var ?string $status */
/** @var ?string $category */
use App\Services\Csrf;
$rows = $result['rows'];
$toggleCsrf = Csrf::token('admin_project_toggle');
?>
<section class="admin-page">
    <div class="admin-page__head admin-page__head--row">
        <div>
            <p class="eyebrow">Projects</p>
            <h1>Manage projects</h1>
            <p class="muted"><?= (int) $paginator->total ?> project<?= $paginator->total === 1 ? '' : 's' ?></p>
        </div>
        <a class="btn btn--primary" href="<?= e(url('/admin/projects/create')) ?>">+ New project</a>
    </div>

    <form class="admin-filters" method="get" action="<?= e(url('/admin/projects')) ?>" role="search">
        <input type="search" name="q" placeholder="Search title, slug, study area…"
               value="<?= e($q ?? '') ?>" maxlength="100">
        <select name="status">
            <option value="">All statuses</option>
            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="draft"     <?= $status === 'draft'     ? 'selected' : '' ?>>Draft</option>
        </select>
        <select name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c['slug']) ?>" <?= $category === $c['slug'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?> (<?= (int) $c['total_projects'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn--primary" type="submit">Filter</button>
        <?php if ($q || $status || $category): ?>
            <a class="btn btn--ghost" href="<?= e(url('/admin/projects')) ?>">Reset</a>
        <?php endif; ?>
    </form>

    <?php if ($rows === []): ?>
        <div class="empty-state">
            <p>No projects match these filters.</p>
            <a class="btn btn--primary" href="<?= e(url('/admin/projects/create')) ?>">Create one</a>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th><th>Category</th><th>Status</th><th>Featured</th>
                    <th>Updated</th><th class="t-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $p): ?>
                <tr>
                    <td>
                        <a href="<?= e(url('/admin/projects/' . (int) $p['id'] . '/edit')) ?>">
                            <?= e($p['title']) ?>
                        </a>
                        <div class="t-muted"><?= e($p['slug']) ?></div>
                    </td>
                    <td>
                        <span class="cat-dot" style="--c: <?= e($p['category_color'] ?: '#14B8A6') ?>"></span>
                        <?= e($p['category_name']) ?>
                    </td>
                    <td><span class="status-pill status-pill--<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                    <td>
                        <form method="post" action="<?= e(url('/admin/projects/' . (int) $p['id'] . '/toggle-featured')) ?>" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e($toggleCsrf) ?>">
                            <input type="hidden" name="featured" value="<?= $p['featured'] ? '0' : '1' ?>">
                            <button type="submit" class="btn-link"><?= $p['featured'] ? '★ Featured' : '☆ Feature' ?></button>
                        </form>
                    </td>
                    <td><?= e(fmt_date($p['updated_at'], 'M j, Y')) ?></td>
                    <td class="t-actions">
                        <form method="post" action="<?= e(url('/admin/projects/' . (int) $p['id'] . '/toggle-publish')) ?>" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e($toggleCsrf) ?>">
                            <input type="hidden" name="to" value="<?= $p['status'] === 'published' ? 'draft' : 'published' ?>">
                            <button type="submit" class="btn-link"><?= $p['status'] === 'published' ? 'Unpublish' : 'Publish' ?></button>
                        </form>
                        <a href="<?= e(url('/admin/projects/' . (int) $p['id'] . '/edit')) ?>">Edit</a>
                        <a class="t-danger" href="<?= e(url('/admin/projects/' . (int) $p['id'] . '/delete')) ?>">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        $baseQs = $qs;
        $link = function (int $page) use ($baseQs): string {
            $baseQs['page'] = $page;
            return url('/admin/projects?' . http_build_query(array_filter($baseQs)));
        };
        ?>
        <?php if ($paginator->totalPages > 1): ?>
            <nav class="pagination" aria-label="Pagination">
                <ul class="pagination__list">
                    <?php if ($paginator->hasPrev()): ?>
                        <li><a class="pagination__link" href="<?= e($link($paginator->page - 1)) ?>">← Previous</a></li>
                    <?php endif; ?>
                    <?php foreach ($paginator->window(5) as $n): ?>
                        <li><a class="pagination__link<?= $n === $paginator->page ? ' is-current' : '' ?>" href="<?= e($link($n)) ?>" <?= $n === $paginator->page ? 'aria-current="page"' : '' ?>><?= (int) $n ?></a></li>
                    <?php endforeach; ?>
                    <?php if ($paginator->hasNext()): ?>
                        <li><a class="pagination__link" href="<?= e($link($paginator->page + 1)) ?>">Next →</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>