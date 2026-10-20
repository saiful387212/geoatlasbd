<?php
declare(strict_types=1);
/** @var array $project */
/** @var array $errors */
/** @var array $images */
/** @var array $files */
/** @var array $categories */
use App\Services\Csrf;

$isEdit = !empty($project['id']);
$formCsrf      = Csrf::token('admin_project_form');
$mediaCsrf     = Csrf::token('admin_project_media');
$val = fn(string $k) => e((string) ($project[$k] ?? ''));
$err = fn(string $k) => isset($errors[$k]) ? e($errors[$k]) : null;
?>
<section class="admin-page">
    <div class="admin-page__head">
        <p class="eyebrow"><?= $isEdit ? 'Edit project' : 'New project' ?></p>
        <h1><?= $isEdit ? e($project['title']) : 'Create a project' ?></h1>
        <p><a href="<?= e(url('/admin/projects')) ?>">← Back to projects</a></p>
    </div>

    <?php if (isset($errors['_form'])): ?>
        <div class="flash flash--error" role="alert"><?= e($errors['_form']) ?></div>
    <?php endif; ?>

    <form method="post"
          action="<?= e(url($isEdit ? '/admin/projects/' . (int) $project['id'] : '/admin/projects')) ?>"
          enctype="multipart/form-data"
          class="admin-form">
        <input type="hidden" name="_csrf" value="<?= e($formCsrf) ?>">
        <?php if (!empty($project['published_at'])): ?>
            <input type="hidden" name="_existing_published_at" value="<?= e((string) $project['published_at']) ?>">
        <?php endif; ?>

        <fieldset>
            <legend>Basics</legend>

            <div class="form-field">
                <label for="title">Title *</label>
                <input id="title" name="title" type="text" required maxlength="200"
                       value="<?= $val('title') ?>" data-slug-source>
                <?php if ($err('title')): ?><p class="field-error"><?= $err('title') ?></p><?php endif; ?>
            </div>

            <div class="form-field">
                <label for="slug">Slug *</label>
                <input id="slug" name="slug" type="text" required maxlength="200"
                       value="<?= $val('slug') ?>" pattern="[a-z0-9\-]+"
                       placeholder="auto-generated from title if left blank"
                       data-slug-target>
                <?php if ($err('slug')): ?><p class="field-error"><?= $err('slug') ?></p><?php endif; ?>
            </div>

            <div class="form-field">
                <label for="category_id">Category *</label>
                <select id="category_id" name="category_id" required>
                    <option value="">— Choose —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) $project['category_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($err('category_id')): ?><p class="field-error"><?= $err('category_id') ?></p><?php endif; ?>
            </div>

            <div class="form-field">
                <label for="summary">Summary *</label>
                <input id="summary" name="summary" type="text" required maxlength="400"
                       value="<?= $val('summary') ?>">
                <?php if ($err('summary')): ?><p class="field-error"><?= $err('summary') ?></p><?php endif; ?>
            </div>

            <div class="form-field">
                <label for="description">Overview / description</label>
                <textarea id="description" name="description" rows="6"><?= $val('description') ?></textarea>
                <p class="field-hint">Basic HTML allowed: &lt;p&gt;, &lt;h3&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;a&gt;.</p>
            </div>
        </fieldset>

        <fieldset>
            <legend>Case study</legend>

            <div class="form-field">
                <label for="study_area">Study area</label>
                <input id="study_area" name="study_area" type="text" maxlength="150"
                       value="<?= $val('study_area') ?>">
            </div>

            <div class="form-field">
                <label for="objectives">Objectives (HTML allowed)</label>
                <textarea id="objectives" name="objectives" rows="4"><?= $val('objectives') ?></textarea>
            </div>

            <div class="form-field">
                <label for="research_questions">Research questions (one per line)</label>
                <textarea id="research_questions" name="research_questions" rows="4"><?= $val('research_questions') ?></textarea>
            </div>

            <div class="form-field">
                <label for="data_sources">Data sources (comma or · separated)</label>
                <input id="data_sources" name="data_sources" type="text" maxlength="2000"
                       value="<?= $val('data_sources') ?>">
            </div>

            <div class="form-field">
                <label for="data_year">Data year(s)</label>
                <input id="data_year" name="data_year" type="text" maxlength="60"
                       value="<?= $val('data_year') ?>">
            </div>

            <div class="form-field">
                <label for="methodology">Methodology (HTML allowed)</label>
                <textarea id="methodology" name="methodology" rows="5"><?= $val('methodology') ?></textarea>
            </div>

            <div class="form-field">
                <label for="tools_used">Tools used (comma or · separated)</label>
                <input id="tools_used" name="tools_used" type="text" maxlength="255"
                       value="<?= $val('tools_used') ?>">
            </div>

            <div class="form-field">
                <label for="findings">Findings (HTML allowed)</label>
                <textarea id="findings" name="findings" rows="4"><?= $val('findings') ?></textarea>
            </div>

            <div class="form-field">
                <label for="limitations">Limitations (HTML allowed)</label>
                <textarea id="limitations" name="limitations" rows="4"><?= $val('limitations') ?></textarea>
            </div>
        </fieldset>

        <fieldset>
            <legend>Map &amp; metadata</legend>

            <div class="form-row">
                <div class="form-field">
                    <label for="latitude">Latitude</label>
                    <input id="latitude" name="latitude" type="text" maxlength="20"
                           value="<?= $val('latitude') ?>" placeholder="23.810300">
                    <?php if ($err('latitude')): ?><p class="field-error"><?= $err('latitude') ?></p><?php endif; ?>
                </div>
                <div class="form-field">
                    <label for="longitude">Longitude</label>
                    <input id="longitude" name="longitude" type="text" maxlength="20"
                           value="<?= $val('longitude') ?>" placeholder="90.412500">
                    <?php if ($err('longitude')): ?><p class="field-error"><?= $err('longitude') ?></p><?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="zoom">Map zoom (1–19)</label>
                    <input id="zoom" name="zoom" type="number" min="1" max="19"
                           value="<?= (int) ($project['zoom'] ?? 8) ?>">
                </div>
                <div class="form-field">
                    <label for="tags">Tags (comma-separated)</label>
                    <input id="tags" name="tags" type="text" maxlength="255"
                           value="<?= $val('tags') ?>">
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Media</legend>

            <div class="form-field">
                <label for="cover_image">Cover image (JPG/PNG/WebP, ≤ 5 MB)</label>
                <input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp">
                <?php if (!empty($project['cover_image'])): ?>
                    <div class="cover-preview">
                        <img src="<?= e(upload_url(safe_upload_path($project['cover_image'], 'images') ?? '')) ?>"
                             alt="" width="120">
                        <label><input type="checkbox" name="remove_cover" value="1"> Remove current cover</label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="gallery">Gallery images (multiple)</label>
                <input id="gallery" name="gallery[]" type="file" multiple
                       accept="image/jpeg,image/png,image/webp">
            </div>

            <?php if (!empty($images)): ?>
                <div class="media-grid">
                    <?php foreach ($images as $img): ?>
                        <div class="media-tile">
                            <img src="<?= e(upload_url(safe_upload_path($img['file_path'], 'images') ?? '')) ?>" alt="">
                            <form method="post"
                                  action="<?= e(url('/admin/projects/images/' . (int) $img['id'] . '/delete')) ?>"
                                  onsubmit="return confirm('Remove this image?');">
                                <input type="hidden" name="_csrf" value="<?= e($mediaCsrf) ?>">
                                <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                                <button class="btn btn--ghost btn--sm" type="submit">Remove</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="form-field">
                <label for="report_pdf">Report PDF (≤ 10 MB)</label>
                <input id="report_pdf" name="report_pdf" type="file" accept="application/pdf">
            </div>

            <?php if (!empty($files)): ?>
                <ul class="admin-list">
                    <?php foreach ($files as $f): ?>
                        <li>
                            <?= e($f['original_name']) ?>
                            <form method="post"
                                  action="<?= e(url('/admin/projects/files/' . (int) $f['id'] . '/delete')) ?>"
                                  onsubmit="return confirm('Remove this file?');" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e($mediaCsrf) ?>">
                                <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                                <button class="btn-link t-danger" type="submit">Remove</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </fieldset>

        <fieldset>
            <legend>Publication</legend>

            <div class="form-field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="draft"     <?= ($project['status'] ?? 'draft') === 'draft'     ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= ($project['status'] ?? 'draft') === 'published' ? 'selected' : '' ?>>Published</option>
                </select>
            </div>

            <div class="form-field form-field--check">
                <label><input type="checkbox" name="featured" value="1" <?= !empty($project['featured']) ? 'checked' : '' ?>> Featured on homepage</label>
            </div>
        </fieldset>

        <p class="admin-form__actions">
            <button class="btn btn--primary" type="submit"><?= $isEdit ? 'Save changes' : 'Create project' ?></button>
            <a class="btn btn--ghost" href="<?= e(url('/admin/projects')) ?>">Cancel</a>
            <?php if ($isEdit): ?>
                <a class="btn btn--ghost t-danger" href="<?= e(url('/admin/projects/' . (int) $project['id'] . '/delete')) ?>">Delete project</a>
            <?php endif; ?>
        </p>
    </form>
</section>