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

    $basePath = parse_url(url(), PHP_URL_PATH) ?? '';
    $basePath = '/' . trim($basePath, '/');
    if ($basePath !== '/' && ($currentPath === $basePath || str_starts_with($currentPath, $basePath . '/'))) {
        $currentPath = substr($currentPath, strlen($basePath)) ?: '/';
    }

    if ($hrefPath === '/') {
        return $currentPath === '/';
    }
    return $currentPath === $hrefPath || str_starts_with($currentPath, $hrefPath . '/');
}