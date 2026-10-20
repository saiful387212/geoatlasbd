<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Immutable pagination value object.
 * Computes page, per-page, offset, total pages, and link window.
 */
final class Paginator
{
    public readonly int $page;
    public readonly int $perPage;
    public readonly int $total;
    public readonly int $totalPages;
    public readonly int $offset;

    public function __construct(int $total, int $page, int $perPage = 9)
    {
        $this->total      = max(0, $total);
        $this->perPage    = max(1, min($perPage, 48));
        $this->totalPages = max(1, (int) ceil($this->total / $this->perPage));
        $this->page       = max(1, min($page, $this->totalPages));
        $this->offset     = ($this->page - 1) * $this->perPage;
    }

    public function hasPrev(): bool { return $this->page > 1; }
    public function hasNext(): bool { return $this->page < $this->totalPages; }

    /**
     * Page numbers to render, e.g. [1,2,3,4,5] around the current page.
     * @return int[]
     */
    public function window(int $size = 5): array
    {
        $size   = max(3, $size);
        $half   = intdiv($size, 2);
        $start  = max(1, $this->page - $half);
        $end    = min($this->totalPages, $start + $size - 1);
        $start  = max(1, $end - $size + 1);
        return range($start, $end);
    }
}