<?php
declare(strict_types=1);

/**
 * One-shot session flash messages.
 * Usage:  flash('success', 'Project saved.');
 *         $msg = flash_get('success');
 */
function flash(string $key, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['_flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }
    $msg = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $msg;
}