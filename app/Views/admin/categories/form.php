<?php
declare(strict_types=1);
/** @var array $category */
/** @var array $errors */
use App\Services\Csrf;
$isEdit = !empty($category['id']);
$csrf = Csrf::token('admin_category_form');
$val = fn(string $k) => e((string) ($category[$k] ?? ''));
$err = fn(string $k) => isset($errors[$k]) ? e($errors[$k]) : null;
?>
<section class="admin-page">
    <div class="admin-page__head">
        <p class="eyebrow"><?= $isEdit ? 'Edit category' : 'New category' ?></p>
        <h1><?= $isEdit ? e($category['name']) : 'Create a category' ?></h1>
        <p><a href="<?= e(url('/admin/categories')) ?>">← Back to categories</a></p>
    </div>

    <?php if (isset($errors['_form'])): ?>
        <div class="flash flash--error" role="alert"><?= e($errors['_form']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url($isEdit ? '/admin/categories/' . (int) $category['id'] : '/admin/categories')) ?>" class="admin-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <div class="form-field">
            <label for="name">Name *</label>
            <input id="name" name="name" type="text" required maxlength="80" value="<?= $val('name') ?>" data-slug-source>
            <?php if ($err('name')): ?><p class="field-error"><?= $err('name') ?></p><?php endif; ?>
        </div>

        <div class="form-field">
            <label for="slug">Slug *</label>
            <input id="slug" name="slug" type="text" required maxlength="100" value="<?= $val('slug') ?>"
                   pattern="[a-z0-9\-]+" data-slug-target>
            <?php if ($err('slug')): ?><p class="field-error"><?= $err('slug') ?></p><?php endif; ?>
        </div>

        <div class="form-field">
            <label for="description">Description</label>
            <input id="description" name="description" type="text" maxlength="255" value="<?= $val('description') ?>">
        </div>

        <div class="form-row">
            <div class="form-field">
                <label for="color">Color</label>
                <input id="color" name="color" type="color" value="<?= e($category['color'] ?: '#14B8A6') ?>">
            </div>
            <div class="form-field">
                <label for="sort_order">Sort order</label>
                <input id="sort_order" name="sort_order" type="number" value="<?= (int) ($category['sort_order'] ?? 0) ?>">
            </div>
        </div>

        <div class="form-field">
            <label for="icon">Icon (optional)</label>
            <input id="icon" name="icon" type="text" maxlength="60" value="<?= $val('icon') ?>">
        </div>

        <p class="admin-form__actions">
            <button class="btn btn--primary" type="submit"><?= $isEdit ? 'Save changes' : 'Create category' ?></button>
            <a class="btn btn--ghost" href="<?= e(url('/admin/categories')) ?>">Cancel</a>
        </p>
    </form>
</section>