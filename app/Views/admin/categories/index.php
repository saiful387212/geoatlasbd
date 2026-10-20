<?php
declare(strict_types=1);
/** @var array $categories */
/** @var string $csrfAction */
?>
<section class="admin-page">
    <div class="admin-page__head admin-page__head--row">
        <div>
            <p class="eyebrow">Categories</p>
            <h1>Manage categories</h1>
            <p class="muted"><?= count($categories) ?> categor<?= count($categories) === 1 ? 'y' : 'ies' ?></p>
        </div>
        <a class="btn btn--primary" href="<?= e(url('/admin/categories/create')) ?>">+ New category</a>
    </div>

    <table class="admin-table">
        <thead><tr><th>Name</th><th>Slug</th><th>Projects</th><th>Order</th><th class="t-actions">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($categories as $c): ?>
            <tr>
                <td>
                    <span class="cat-dot" style="--c: <?= e($c['color'] ?: '#14B8A6') ?>"></span>
                    <?= e($c['name']) ?>
                </td>
                <td><?= e($c['slug']) ?></td>
                <td><?= (int) $c['published_projects'] ?> published / <?= (int) $c['total_projects'] ?> total</td>
                <td><?= (int) $c['sort_order'] ?></td>
                <td class="t-actions">
                    <a href="<?= e(url('/admin/categories/' . (int) $c['id'] . '/edit')) ?>">Edit</a>
                    <form method="post" action="<?= e(url('/admin/categories/' . (int) $c['id'] . '/delete')) ?>"
                          onsubmit="return confirm('Delete this category? Fails if projects use it.');"
                          class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e($csrfAction) ?>">
                        <button class="btn-link t-danger" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>