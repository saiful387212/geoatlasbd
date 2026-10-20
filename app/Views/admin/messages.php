<?php
declare(strict_types=1);
/** @var array $messages */
?>
<section class="admin-page">
    <div class="admin-page__head">
        <p class="eyebrow">Inbox</p>
        <h1>Contact messages</h1>
        <p class="muted">Review messages submitted through the public contact form.</p>
    </div>
    <?php if ($databaseError !== null): ?><div class="flash flash--error" role="alert"><?= e($databaseError) ?></div><?php endif; ?>
    <?php if ($messages === []): ?>
        <div class="admin-panel"><p class="muted">No messages found.</p></div>
    <?php else: ?>
        <div class="admin-message-list">
            <?php foreach ($messages as $message): ?>
                <article class="admin-panel admin-message<?= empty($message['is_read']) ? ' admin-message--unread' : '' ?>">
                    <header class="admin-message__head">
                        <div>
                            <p class="eyebrow"><?= !empty($message['is_spam']) ? 'Flagged as spam' : (empty($message['is_read']) ? 'Unread' : 'Read') ?></p>
                            <h2><?= e($message['subject']) ?></h2>
                            <p class="muted"><?= e($message['name']) ?> · <a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a></p>
                        </div>
                        <time><?= e($message['submitted_at'] ?? '') ?></time>
                    </header>
                    <p class="admin-message__body"><?= nl2br(e($message['message'])) ?></p>
                    <div class="admin-actions">
                        <form method="post" action="<?= e(url('/admin/messages/update')) ?>">
                            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                            <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                            <input type="hidden" name="action" value="<?= empty($message['is_read']) ? 'read' : 'unread' ?>">
                            <button class="btn btn--ghost btn--sm" type="submit"><?= empty($message['is_read']) ? 'Mark read' : 'Mark unread' ?></button>
                        </form>
                        <form method="post" action="<?= e(url('/admin/messages/update')) ?>" onsubmit="return confirm('Permanently delete this message?');">
                            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                            <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button class="btn btn--danger btn--sm" type="submit">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
