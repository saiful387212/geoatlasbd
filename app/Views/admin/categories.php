<?php
declare(strict_types=1);
/** @var array $categories */
/** @var ?array $editCategory */
?>
<section class="admin-page">
    <div class="admin-page__head">
        <p class="eyebrow">Content management</p>
        <h1>Categories</h1>
        <p class="muted">Organize projects into browsable themes.</p>
    </div>
    <?php if ($databaseError !== null): ?><div class="flash flash--error" role="alert"><?= e($databaseError) ?></div><?php endif; ?>
    <div class="admin-content-grid">
        <section class="admin-panel">
            <h2><?= $editCategory ? 'Edit category' : 'Add category' ?></h2>
            <form method="post" action="<?= e(url('/admin/categories/save')) ?>">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <?php if ($editCategory): ?><input type="hidden" name="id" value="<?= (int) $editCategory['id'] ?>"><?php endif; ?>
                <div class="form-field"><label for="category-name">Name</label>
                    <input id="category-name" name="name" required maxlength="100" value="<?= e($editCategory['name'] ?? '') ?>"></div>
                <div class="form-field"><label for="category-slug">URL slug</label>
                    <input id="category-slug" name="slug" maxlength="100" pattern="[a-z0-9]+(-[a-z0-9]+)*"
                           placeholder="generated-from-name" value="<?= e($editCategory['slug'] ?? '') ?>"></div>
                <div class="form-field"><label for="category-description">Description</label>
                    <textarea id="category-description" name="description" rows="3" maxlength="500"><?= e($editCategory['description'] ?? '') ?></textarea></div>
                <div class="form-field"><label for="category-color">Color (hex)</label>
                    <input id="category-color" name="color" type="text" maxlength="7" pattern="#[0-9a-fA-F]{6}"
                           placeholder="#14B8A6" value="<?= e($editCategory['color'] ?? '') ?>"></div>
                <div class="form-field"><label for="category-icon">Icon</label>
                    <input id="category-icon" name="icon" maxlength="50" value="<?= e($editCategory['icon'] ?? '') ?>"></div>
                <div class="form-field"><label for="category-order">Sort order</label>
                    <input id="category-order" name="sort_order" type="number" value="<?= (int) ($editCategory['sort_order'] ?? 0) ?>"></div>
                <p class="form-actions"><button class="btn btn--primary" type="submit">Save category</button>
                    <?php if ($editCategory): ?><a class="btn btn--ghost" href="<?= e(url('/admin/categories')) ?>">Cancel</a><?php endif; ?>
                </p>
            </form>
        </section>
        <section class="admin-panel">
            <h2>All categories <span class="admin-count"><?= count($categories) ?></span></h2>
            <?php if ($categories === []): ?><p class="muted">No categories found.</p>
            <?php else: ?>
                <div class="admin-table-wrap"><table class="admin-table">
                    <thead><tr><th>Name</th><th>Slug</th><th>Published projects</th><th>Actions</th></tr></thead>
                    <tbody><?php foreach ($categories as $category): ?>
                        <tr>
                            <td><span class="admin-color-dot" style="--category-color: <?= e($category['color'] ?: '#14B8A6') ?>"></span><?= e($category['name']) ?></td>
                            <td><?= e($category['slug']) ?></td>
                            <td><?= (int) $category['project_count'] ?></td>
                            <td class="admin-actions">
                                <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/categories?edit=' . (int) $category['id'])) ?>">Edit</a>
                                <form method="post" action="<?= e(url('/admin/categories/delete')) ?>" onsubmit="return confirm('Delete this category? Categories used by projects cannot be deleted.');">
                                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                                    <button class="btn btn--danger btn--sm" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php endif; ?>
        </section>
    </div>
</section>
