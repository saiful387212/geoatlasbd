<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Middleware\SecurityHeaders;
use App\Router;
use App\Controllers\HomeController;
use App\Controllers\AboutController;
use App\Controllers\ContactController;
use App\Controllers\ProjectController;
use App\Controllers\SearchController;
use App\Controllers\AuthController;
use App\Controllers\AdminDashboardController;

SecurityHeaders::apply();

$router = new Router();

// ---- Public ----
$router->get('/',                fn()      => (new HomeController())->show());
$router->get('/about',           fn()      => (new AboutController())->show());
$router->get('/projects',        fn()      => (new ProjectController())->index());
$router->get('/projects/{slug}', fn($p)    => (new ProjectController())->show($p));
$router->get('/search',          fn()      => (new SearchController())->index());
$router->get('/contact',         fn()      => (new ContactController())->show());
$router->post('/contact',        fn()      => (new ContactController())->submit());

// ---- Admin auth ----
$router->get('/admin/login',     fn()      => (new AuthController())->showLogin());
$router->post('/admin/login',    fn()      => (new AuthController())->login());
$router->post('/admin/logout',   fn()      => (new AuthController())->logout());

// ---- Admin protected ----
$router->get('/admin',           fn()      => (new AdminDashboardController())->index());
$router->get('/admin/dashboard', fn()      => (new AdminDashboardController())->index());

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');