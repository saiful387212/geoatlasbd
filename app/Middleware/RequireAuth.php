<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Services\Auth;

final class RequireAuth
{
    /**
     * Terminate the request if not authenticated.
     * Returns the authenticated user record for convenience.
     */
    public static function check(): array
    {
        $user = Auth::user();
        if ($user === null) {
            // Preserve intended destination (only for GET; POSTs must not be replayed).
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $uri    = (string) ($_SERVER['REQUEST_URI'] ?? '/admin');
            if ($method === 'GET' && str_starts_with($uri, '/admin') && $uri !== '/admin/login') {
                $_SESSION['_intended'] = $uri;
            }
            header('Location: ' . url('/admin/login'), true, 303);
            exit;
        }
        return $user;
    }

    /**
     * For POST endpoints: reject with 403 rather than redirect (so fetch calls see the status).
     */
    public static function checkJson(): array
    {
        $user = Auth::user();
        if ($user === null) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        return $user;
    }
}