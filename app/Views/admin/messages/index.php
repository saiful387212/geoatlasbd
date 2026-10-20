<?php
declare(strict_types=1);
/** @var array $messages */
/** @var \App\Services\Paginator $paginator */
/** @var string $view */
/** @var string $csrfAction */
?>

<section class="admin-page">
    <div class="admin-page__head admin-page__head--row">
        <div>
            <p class="eyebrow">Messages</p>
            <h1>Contact Inbox</h1>
            <p class="muted">Manage messages sent from the contact form.</p>
        </div>
    </div>

    <div class="admin-tabs" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; border-bottom: 1px solid var(--line); padding-bottom: .5rem;">
        <a href="<?= e(url('/admin/messages?view=inbox')) ?>" class="<?= $view === 'inbox' ? 'is-active' : '' ?>" style="color: var(--light); font-weight: 600; <?= $view === 'inbox' ? 'color: var(--teal);' : '' ?>">Inbox</a>
        <a href="<?= e(url('/admin/messages?view=spam')) ?>" class="<?= $view === 'spam' ? 'is-active' : '' ?>" style="color: var(--muted); <?= $view === 'spam' ? 'color: var(--teal);' : '' ?>">Spam</a>
        <a href="<?= e(url('/admin/messages?view=all')) ?>" class="<?= $view === 'all' ? 'is-active' : '' ?>" style="color: var(--muted); <?= $view === 'all' ? 'color: var(--teal);' : '' ?>">All</a>
    </div>

    <?php if ($messages === []): ?>
        <div class="empty-state">
            <p>No messages found.</p>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>From</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th class="t-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($messages as $m): ?>
                <tr style="<?= !$m['is_read'] ? 'background: rgba(20,184,166,0.05); font-weight: 600;' : '' ?>">
                    <td>
                        <?php if ($m['is_spam']): ?>
                            <span class="status-pill status-pill--draft" style="background: rgba(239,68,68,0.2); color: #FCA5A5;">Spam</span>
                        <?php elseif (!$m['is_read']): ?>
                            <span class="status-pill status-pill--published">New</span>
                        <?php else: ?>
                            <span class="status-pill status-pill--draft">Read</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= e($m['name']) ?></strong><br>
                        <span class="t-muted"><?= e($m['email']) ?></span>
                    </td>
                    <td>
                        <a href="<?= e(url('/admin/messages/' . $m['id'])) ?>">
                            <?= e($m['subject']) ?>
                        </a>
                        <div class="t-muted" style="font-weight: 400; font-size: 0.85em; margin-top: 4px;">
                            <?= e($m['preview']) ?>...
                        </div>
                    </td>
                    <td><?= e(fmt_date($m['created_at'], 'M j, Y H:i')) ?></td>
                    <td class="t-actions">
                        <form method="post" action="<?= e(url('/admin/messages/' . $m['id'] . '/action')) ?>" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e($csrfAction) ?>">
                            <input type="hidden" name="redirect" value="/admin/messages?view=<?= e($view) ?>">
                            
                            <?php if ($m['is_spam']): ?>
                                <input type="hidden" name="action" value="mark_read">
                                <button class="btn-link" type="submit">Not Spam</button>
                            <?php else: ?>
                                <input type="hidden" name="action" value="mark_spam">
                                <button class="btn-link t-danger" type="submit">Spam</button>
                            <?php endif; ?>
                            
                            <input type="hidden" name="action" value="delete">
                            <button class="btn-link t-danger" type="submit" onclick="return confirm('Delete this message?');">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        $baseQs = ['view' => $view];
        $link = function (int $page) use ($baseQs): string {
            $baseQs['page'] = $page;
            return url('/admin/messages?' . http_build_query(array_filter($baseQs)));
        };
        ?>
        <?php if ($paginator->totalPages > 1): ?>
            <nav class="pagination" aria-label="Pagination">
                <ul class="pagination__list">
                    <?php if ($paginator->hasPrev()): ?>
                        <li><a class="pagination__link" href="<?= e($link($paginator->page - 1)) ?>">← Previous</a></li>
                    <?php endif; ?>
                    <?php foreach ($paginator->window(5) as $n): ?>
                        <li><a class="pagination__link<?= $n === $paginator->page ? ' is-current' : '' ?>" href="<?= e($link($n)) ?>"><?= (int) $n ?></a></li>
                    <?php endforeach; ?>
                    <?php if ($paginator->hasNext()): ?>
                        <li><a class="pagination__link" href="<?= e($link($paginator->page + 1)) ?>">Next →</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>