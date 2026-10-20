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
use App\Controllers\AdminProjectController;
use App\Controllers\AdminCategoryController;
use App\Controllers\AdminContentController;

SecurityHeaders::apply();

$router = new Router();

// ---- Public ----
$router->get('/',                fn()   => (new HomeController())->show());
$router->get('/about',           fn()   => (new AboutController())->show());
$router->get('/projects',        fn()   => (new ProjectController())->index());
$router->get('/projects/{slug}', fn($p) => (new ProjectController())->show($p));
$router->get('/search',          fn()   => (new SearchController())->index());
$router->get('/contact',         fn()   => (new ContactController())->show());
$router->post('/contact',        fn()   => (new ContactController())->submit());

// ---- Admin auth ----
$router->get('/admin/login',     fn()   => (new AuthController())->showLogin());
$router->post('/admin/login',    fn()   => (new AuthController())->login());
$router->post('/admin/logout',   fn()   => (new AuthController())->logout());

// ---- Admin dashboard ----
$router->get('/admin',           fn()   => (new AdminDashboardController())->index());
$router->get('/admin/dashboard', fn()   => (new AdminDashboardController())->index());

// ---- Admin projects ----
$router->get('/admin/projects',                       fn()   => (new AdminProjectController())->index());
$router->get('/admin/projects/create',                fn()   => (new AdminProjectController())->create());
$router->post('/admin/projects',                      fn()   => (new AdminProjectController())->store());
$router->get('/admin/projects/{id}/edit',             fn($p) => (new AdminProjectController())->edit($p));
$router->post('/admin/projects/{id}',                 fn($p) => (new AdminProjectController())->update($p));
$router->get('/admin/projects/{id}/delete',           fn($p) => (new AdminProjectController())->confirmDelete($p));
$router->post('/admin/projects/{id}/delete',          fn($p) => (new AdminProjectController())->destroy($p));
$router->post('/admin/projects/{id}/toggle-publish',  fn($p) => (new AdminProjectController())->togglePublish($p));
$router->post('/admin/projects/{id}/toggle-featured', fn($p) => (new AdminProjectController())->toggleFeatured($p));
$router->post('/admin/projects/images/{imageId}/delete', fn($p) => (new AdminProjectController())->destroyImage($p));
$router->post('/admin/projects/files/{fileId}/delete',   fn($p) => (new AdminProjectController())->destroyFile($p));

// ---- Admin categories ----
$router->get('/admin/categories',            fn()   => (new AdminCategoryController())->index());
$router->get('/admin/categories/create',     fn()   => (new AdminCategoryController())->create());
$router->post('/admin/categories',           fn()   => (new AdminCategoryController())->store());
$router->get('/admin/categories/{id}/edit',  fn($p) => (new AdminCategoryController())->edit($p));
$router->post('/admin/categories/{id}',      fn($p) => (new AdminCategoryController())->update($p));
$router->post('/admin/categories/{id}/delete', fn($p) => (new AdminCategoryController())->destroy($p));

use App\Controllers\AdminMessageController;

// ... (আগের রাউটগুলো)

// ---- Admin messages ----
$router->get('/admin/messages',                 fn()   => (new AdminMessageController())->index());
$router->get('/admin/messages/{id}',            fn($p) => (new AdminMessageController())->show($p));
$router->post('/admin/messages/{id}/action',    fn($p) => (new AdminMessageController())->action($p));

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');