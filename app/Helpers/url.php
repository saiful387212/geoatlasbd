<?php
declare(strict_types=1);

/**
 * Build a same-origin URL using the path where the front controller is installed.
 * This avoids stale APP_URL hostnames breaking production assets and navigation.
 */
function url(string $path = ''): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if ($scriptName !== '') {
        $scriptPath = parse_url($scriptName, PHP_URL_PATH) ?: $scriptName;
        $base = rtrim(str_replace('\\', '/', dirname($scriptPath)), '/');
        if ($base === '.' || $base === '\\') {
            $base = '';
        }
    } else {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $configuredPath = parse_url($config['app']['url'], PHP_URL_PATH) ?: '';
        $base = rtrim($configuredPath, '/');
    }

    return $base . ($path === '' ? '/' : '/' . ltrim($path, '/'));
}

/** Public uploads URL builder. */
function upload_url(string $relativePath): string
{
    return url('/uploads/' . ltrim($relativePath, '/'));
}