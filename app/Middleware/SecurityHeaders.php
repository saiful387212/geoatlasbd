<?php
declare(strict_types=1);

namespace App\Middleware;

final class SecurityHeaders
{
    public static function apply(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(self), camera=(), microphone=()');

        // CSP: self only + OSM tiles for Phase 4 maps.
        // Inline styles allowed for Leaflet; tighten further in Phase 9.
        header(
            "Content-Security-Policy: "
            . "default-src 'self'; "
            . "img-src 'self' data: https://*.tile.openstreetmap.org https://unpkg.com; "
            . "style-src 'self' 'unsafe-inline' https://unpkg.com; "
            . "script-src 'self' https://unpkg.com; "
            . "font-src 'self' data:; "
            . "connect-src 'self'; "
            . "frame-ancestors 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self'"
        );
    }
}