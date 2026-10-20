<?php
declare(strict_types=1);
/** @var array $user */
/** @var string $csrf */
/** @var array $errors */

$val = fn(string $k) => ''; // We don't repopulate password fields
$err = fn(string $k) => isset($errors[$k]) ? e($errors[$k]) : null;
?>

<section class="admin-page">
    <div class="admin-page__head">
        <p class="eyebrow">Account</p>
        <h1>Change Password</h1>
        <p class="muted">Update your administrator password. Use a long, unique passphrase.</p>
    </div>

    <?php if (isset($errors['_form'])): ?>
        <div class="flash flash--error" role="alert"><?= e($errors['_form']) ?></div>
    <?php endif; ?>

    <div class="admin-panel" style="max-width: 600px;">
        <form method="post" action="<?= e(url('/admin/account/password')) ?>" class="admin-form">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

            <div class="form-field">
                <label for="current_password">Current Password</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
                <?php if ($err('current_password')): ?><p class="field-error"><?= $err('current_password') ?></p><?php endif; ?>
            </div>

            <div class="form-field">
                <label for="new_password">New Password</label>
                <input id="new_password" name="new_password" type="password" required minlength="12" autocomplete="new-password">
                <p class="field-hint">Minimum 12 characters.</p>
                <?php if ($err('new_password')): ?><p class="field-error"><?= $err('new_password') ?></p><?php endif; ?>
            </div>

            <div class="form-field">
                <label for="confirm_password">Confirm New Password</label>
                <input id="confirm_password" name="confirm_password" type="password" required autocomplete="new-password">
                <?php if ($err('confirm_password')): ?><p class="field-error"><?= $err('confirm_password') ?></p><?php endif; ?>
            </div>

            <p class="admin-form__actions">
                <button class="btn btn--primary" type="submit">Update Password</button>
                <a class="btn btn--ghost" href="<?= e(url('/admin/dashboard')) ?>">Cancel</a>
            </p>
        </form>
    </div>
</section>