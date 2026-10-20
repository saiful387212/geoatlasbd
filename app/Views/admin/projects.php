<?php
declare(strict_types=1);
/** @var array $projects */
/** @var array $categories */
/** @var ?array $editProject */
?>
<section class="admin-page">
    <div class="admin-page__head">
        <p class="eyebrow">Content management</p>
        <h1>Projects</h1>
        <p class="muted">Create drafts, edit project details, and publish selected work.</p>
    </div>

    <div class="admin-content-grid">
        <section class="admin-panel">
            <h2><?= $editProject ? 'Edit project' : 'Add project' ?></h2>
            <?php if ($categories === []): ?>
                <p class="muted">Create a category before adding a project.</p>
            <?php else: ?>
                <form method="post" action="<?= e(url('/admin/projects/save')) ?>">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <?php if ($editProject): ?>
                        <input type="hidden" name="id" value="<?= (int) $editProject['id'] ?>">
                    <?php endif; ?>
                    <div class="form-field">
                        <label for="project-title">Title</label>
                        <input id="project-title" name="title" required maxlength="180" value="<?= e($editProject['title'] ?? '') ?>">
                    </div>
                    <div class="form-field">
                        <label for="project-slug">URL slug</label>
                        <input id="project-slug" name="slug" maxlength="180" pattern="[a-z0-9]+(-[a-z0-9]+)*"
                               value="<?= e($editProject['slug'] ?? '') ?>" placeholder="generated-from-title">
                    </div>
                    <div class="form-field">
                        <label for="project-category">Category</label>
                        <select id="project-category" name="category_id" required>
                            <option value="">Choose a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>"
                                    <?= (int) ($editProject['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="project-summary">Summary</label>
                        <textarea id="project-summary" name="summary" rows="3" maxlength="500" required><?= e($editProject['summary'] ?? '') ?></textarea>
                    </div>
                    <div class="form-field">
                        <label for="project-description">Description (HTML text is sanitized)</label>
                        <textarea id="project-description" name="description" rows="6"><?= e($editProject['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-field">
                        <label for="project-area">Study area</label>
                        <input id="project-area" name="study_area" maxlength="255" value="<?= e($editProject['study_area'] ?? '') ?>">
                    </div>
                    <div class="form-field">
                        <label for="project-tags">Tags (comma-separated)</label>
                        <input id="project-tags" name="tags" maxlength="1000" value="<?= e($editProject['tags'] ?? '') ?>">
                    </div>
                    <div class="form-field">
                        <label for="project-status">Status</label>
                        <select id="project-status" name="status">
                            <option value="draft" <?= ($editProject['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= ($editProject['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                        </select>
                    </div>
                    <label class="admin-checkbox">
                        <input type="checkbox" name="featured" value="1" <?= !empty($editProject['featured']) ? 'checked' : '' ?>>
                        Feature on the homepage
                    </label>
                    <p class="form-actions"><button class="btn btn--primary" type="submit">Save project</button>
                        <?php if ($editProject): ?><a class="btn btn--ghost" href="<?= e(url('/admin/projects')) ?>">Cancel</a><?php endif; ?>
                    </p>
                </form>
            <?php endif; ?>
        </section>

        <section class="admin-panel">
            <h2>All projects <span class="admin-count"><?= count($projects) ?></span></h2>
            <?php if ($projects === []): ?>
                <p class="muted">No projects found.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Project</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><?= e($project['title']) ?></td>
                                <td><?= e($project['category_name'] ?? 'Uncategorized') ?></td>
                                <td><?= e(ucfirst($project['status'])) ?><?= !empty($project['featured']) ? ' · Featured' : '' ?></td>
                                <td class="admin-actions">
                                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/projects?edit=' . (int) $project['id'])) ?>">Edit</a>
                                    <form method="post" action="<?= e(url('/admin/projects/delete')) ?>" onsubmit="return confirm('Delete this project? This cannot be undone.');">
                                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $project['id'] ?>">
                                        <button class="btn btn--danger btn--sm" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>
