<?php
declare(strict_types=1);
/** @var array $message */
/** @var string $csrfAction */
?>

<section class="admin-page">
    <div class="admin-page__head">
        <p class="eyebrow">Message</p>
        <h1><?= e($message['subject']) ?></h1>
        <p><a href="<?= e(url('/admin/messages')) ?>">← Back to Inbox</a></p>
    </div>

    <div class="admin-panel" style="max-width: 800px;">
        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--line); padding-bottom: 1rem; margin-bottom: 1rem;">
            <div>
                <strong>From:</strong> <?= e($message['name']) ?> <br>
                <span class="t-muted">&lt;<?= e($message['email']) ?>&gt;</span>
            </div>
            <div style="text-align: right;">
                <strong>Received:</strong> <?= e(fmt_date($message['created_at'], 'M j, Y \a\t H:i')) ?><br>
                <span class="t-muted">IP Hash: <?= e(substr((string)$message['ip_hash'], 0, 8)) ?>...</span>
            </div>
        </div>

        <div class="prose" style="color: var(--light); white-space: pre-wrap; margin-bottom: 2rem;"><?= e($message['message']) ?></div>

        <div style="border-top: 1px solid var(--line); padding-top: 1rem; display: flex; gap: 1rem;">
            <form method="post" action="<?= e(url('/admin/messages/' . $message['id'] . '/action')) ?>">
                <input type="hidden" name="_csrf" value="<?= e($csrfAction) ?>">
                <input type="hidden" name="redirect" value="/admin/messages">
                
                <?php if ($message['is_spam']): ?>
                    <input type="hidden" name="action" value="mark_read">
                    <button class="btn btn--ghost" type="submit">Mark as Not Spam</button>
                <?php else: ?>
                    <input type="hidden" name="action" value="mark_spam">
                    <button class="btn btn--ghost t-danger" type="submit">Mark as Spam</button>
                <?php endif; ?>
            </form>

            <form method="post" action="<?= e(url('/admin/messages/' . $message['id'] . '/action')) ?>" onsubmit="return confirm('Delete this message permanently?');">
                <input type="hidden" name="_csrf" value="<?= e($csrfAction) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="redirect" value="/admin/messages">
                <button class="btn btn--danger" type="submit">Delete Message</button>
            </form>
        </div>
    </div>
</section>