<?php
declare(strict_types=1);

/**
 * Build an app-relative URL from the configured base URL.
 * Usage: url('/projects') → http://geoatlas.local/projects
 *        url('projects/dhaka') → http://geoatlas.local/projects/dhaka
 */
function url(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $base = rtrim($config['app']['url'], '/');
    }
    if ($path === '') {
        return $base . '/';
    }
    return $base . '/' . ltrim($path, '/');
}

/** Public uploads URL builder. */
function upload_url(string $relativePath): string
{
    return url('/uploads/' . ltrim($relativePath, '/'));
}