<?php
declare(strict_types=1);
/** @var string $csrf */
/** @var ?string $error */
?>
<section class="admin-login">
    <div class="admin-login__card">
        <header class="admin-login__head">
            <img src="<?= e(url('/assets/img/logo.svg')) ?>" alt="" width="40" height="40">
            <h1>Administrator sign in</h1>
            <p class="admin-login__lead">Sign in to manage GIS projects, categories, and messages.</p>
        </header>

        <?php if ($error !== null): ?>
            <div class="flash flash--error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/admin/login')) ?>" autocomplete="on" novalidate>
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

            <div class="form-field">
                <label for="a-email">Email</label>
                <input id="a-email" name="email" type="email" required
                       maxlength="255" autocomplete="username"
                       inputmode="email" autofocus>
            </div>

            <div class="form-field">
                <label for="a-password">Password</label>
                <input id="a-password" name="password" type="password" required
                       maxlength="4096" autocomplete="current-password">
            </div>

            <p class="form-actions">
                <button class="btn btn--primary btn--block" type="submit">Sign in</button>
            </p>
        </form>

        <p class="admin-login__help">
            Administrators are provisioned by CLI (<code>php database/seed_admin.php</code>).
            Public registration is disabled.
        </p>
    </div>
</section>