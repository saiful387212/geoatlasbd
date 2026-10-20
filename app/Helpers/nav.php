<?php
declare(strict_types=1);

/**
 * Returns true if $current path equals $href path (or is a child of it).
 * Used to set aria-current="page" on the nav.
 */
function nav_is_active(string $currentPath, string $hrefPath): bool
{
    $currentPath = '/' . trim(parse_url($currentPath, PHP_URL_PATH) ?? '/', '/');
    $hrefPath    = '/' . trim(parse_url($hrefPath, PHP_URL_PATH) ?? '/', '/');
    if ($hrefPath === '/') {
        return $currentPath === '/';
    }
    return $currentPath === $hrefPath || str_starts_with($currentPath, $hrefPath . '/');
}