<?php
declare(strict_types=1);
/** @var \App\Services\Paginator $paginator */
/** @var array $qs */

if ($paginator->totalPages <= 1) return;

$link = function (int $page) use ($qs): string {
    $qs['page'] = $page;
    return url('/projects?' . http_build_query(array_filter($qs, fn($v) => $v !== null && $v !== '')));
};
?>
<nav class="pagination" aria-label="Pagination">
    <ul class="pagination__list">
        <?php if ($paginator->hasPrev()): ?>
            <li><a class="pagination__link" href="<?= e($link($paginator->page - 1)) ?>" rel="prev">
                <span aria-hidden="true">←</span> Previous
            </a></li>
        <?php endif; ?>

        <?php foreach ($paginator->window(5) as $n): ?>
            <?php $current = $n === $paginator->page; ?>
            <li>
                <a class="pagination__link<?= $current ? ' is-current' : '' ?>"
                   href="<?= e($link($n)) ?>"
                   <?= $current ? 'aria-current="page"' : '' ?>>
                    <?= (int) $n ?>
                </a>
            </li>
        <?php endforeach; ?>

        <?php if ($paginator->hasNext()): ?>
            <li><a class="pagination__link" href="<?= e($link($paginator->page + 1)) ?>" rel="next">
                Next <span aria-hidden="true">→</span>
            </a></li>
        <?php endif; ?>
    </ul>
    <p class="pagination__meta" aria-live="polite">
        Page <?= (int) $paginator->page ?> of <?= (int) $paginator->totalPages ?> ·
        <?= (int) $paginator->total ?> project<?= $paginator->total === 1 ? '' : 's' ?>
    </p>
</nav>