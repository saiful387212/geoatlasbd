<?php
declare(strict_types=1);

namespace App;

/**
 * Very small routing table.
 * Pattern placeholders use {name} and match a single URL segment.
 */
final class Router
{
    /** @var array<string, array<int, array{pattern:string, regex:string, handler:callable}>> */
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $this->routes[$method][] = [
            'pattern' => $pattern,
            'regex'   => '#^' . $regex . '$#',
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        // Strip query string, decode path, normalise
        $path = rawurldecode(parse_url($uri, PHP_URL_PATH) ?? '/');
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            $path = '/';
        }

        // Remove the front controller's directory when installed in a subfolder.
        $scriptDirectory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
        if ($scriptDirectory !== '' && ($path === $scriptDirectory || str_starts_with($path, $scriptDirectory . '/'))) {
            $path = substr($path, strlen($scriptDirectory)) ?: '/';
        }

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['regex'], $path, $m) === 1) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                ($route['handler'])($params);
                return;
            }
        }

        http_response_code(404);
        $this->render404();
    }

    private function render404(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        $title = 'Page Not Found';
        ob_start();
        ?>
        <section class="container" style="padding: 6rem 0; text-align:center;">
            <p class="eyebrow">404</p>
            <h1>This map has no coordinates.</h1>
            <p class="lead">The page you are looking for was not found.</p>
            <p><a class="btn btn--primary" href="<?= e(url('/')) ?>">Back to Home</a></p>
        </section>
        <?php
        $content = ob_get_clean();
        $config = require dirname(__DIR__) . '/config/config.php';
        require dirname(__DIR__) . '/app/Views/layouts/public.php';
    }
}