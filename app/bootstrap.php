<?php
declare(strict_types=1);

/**
 * Application bootstrap.
 * Loads config, autoloads App\* classes, and configures error handling.
 * Idempotent — safe to require from index.php, CLI scripts, and tests.
 */
if (defined('GEOATLAS_BOOTSTRAPPED')) {
    return;
}
define('GEOATLAS_BOOTSTRAPPED', true);

$rootDir = dirname(__DIR__);

// ---- Error handling (dev vs prod) ----
$config = require $rootDir . '/config/config.php';

if ($config['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('error_log', $config['paths']['logs'] . '/app.log');
}

// ---- Minimal PSR-4 style autoloader for App\ ----
spl_autoload_register(function (string $class) use ($rootDir): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path     = $rootDir . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

// Start the session after registering the autoloader (needed for CSRF + flash).
\App\Services\Csrf::startSession();

// ---- Helpers ----
require_once $rootDir . '/app/Helpers/e.php';
require_once $rootDir . '/app/Helpers/url.php';
require_once $rootDir . '/app/Helpers/flash.php';
require_once $rootDir . '/app/Helpers/nav.php';
require_once $rootDir . '/app/Helpers/format.php';
