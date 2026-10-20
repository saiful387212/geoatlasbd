<?php
declare(strict_types=1);
$success = flash_get('success');
$error   = flash_get('error');
if (!$success && !$error) return;
?>
<div class="container flash-stack" aria-live="polite">
    <?php if ($success): ?>
        <div class="flash flash--success" role="status"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="flash flash--error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>
</div>