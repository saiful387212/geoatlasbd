<?php
declare(strict_types=1);

/**
 * Central application configuration.
 * Reads .env once, validates required keys, and returns an array.
 *
 * Usage:  $config = require __DIR__ . '/../config/config.php';
 *         $url = $config['app']['url'];
 */

$rootDir = dirname(__DIR__);
$envFile = $rootDir . '/.env';

if (!is_readable($envFile)) {
    throw new RuntimeException(
        "Missing .env file. Copy .env.example to .env and fill in values."
    );
}

// ---- Minimal .env parser (no external dependency) ----
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    if (!str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $key   = trim($key);
    $value = trim($value);
    // Strip surrounding quotes if present
    if (strlen($value) >= 2) {
        $first = $value[0];
        $last  = $value[strlen($value) - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $value = substr($value, 1, -1);
        }
    }
    $env[$key] = $value;
    // Also populate $_ENV / getenv for libraries that read them.
    $_ENV[$key] = $value;
    putenv("$key=$value");
}

// ---- Required keys ----
$required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'APP_KEY'];
foreach ($required as $key) {
    if (!isset($env[$key]) || $env[$key] === '') {
        throw new RuntimeException("Missing required env key: {$key}");
    }
}

return [
    'root_dir'   => $rootDir,
    'env'        => $env['APP_ENV']  ?? 'local',
    'debug'      => filter_var($env['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOL),
    'app'        => [
        'name' => 'GeoAtlas Bangladesh',
        'url'  => rtrim($env['APP_URL'] ?? 'http://localhost', '/'),
        'key'  => $env['APP_KEY'],
    ],
    'db'         => [
        'host'    => $env['sql300.infinityfree.com'],
        'port'    => (int) ($env['DB_PORT'] ?? 3306),
        'name'    => $env['if0_42235863_geoatlasbd'],
        'user'    => $env['if0_42235863R'],
        'pass'    => $env['irvan387212'],
        'charset' => $env['DB_CHARSET'] ?? 'utf8mb4',
    ],
    'uploads'    => [
        'image_max_bytes' => (int) ($env['UPLOAD_IMAGE_MAX_BYTES'] ?? 5_242_880),
        'pdf_max_bytes'   => (int) ($env['UPLOAD_PDF_MAX_BYTES']   ?? 10_485_760),
        'images_dir'      => $rootDir . '/public/uploads/images',
        'pdfs_dir'        => $rootDir . '/public/uploads/pdfs',
        'images_url'      => '/uploads/images',
        'pdfs_url'        => '/uploads/pdfs',
    ],
    'paths'      => [
        'logs'  => $rootDir . '/storage/logs',
        'cache' => $rootDir . '/storage/cache',
    ],
];