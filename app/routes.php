<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\LogsController;
use App\Controllers\MealPlanController;
use App\Controllers\SettingsController;
use App\Controllers\UpdateController;
use App\Controllers\UserController;
use App\Core\Router;

$router = new Router((string) app_config('app.base_path', ''));

$home = new HomeController($container);
$auth = new AuthController($container);
$admin = new AdminController($container);
$mealPlans = new MealPlanController($container);
$users = new UserController($container);
$settings = new SettingsController($container);
$logs = new LogsController($container);
$updates = new UpdateController($container);

$router->get('/', [$home, 'index']);
$router->get('/branding/app-icon', [$home, 'appIcon']);
$router->get('/impressum', [$home, 'imprint']);
$router->get('/datenschutz', [$home, 'privacy']);
$router->get('/meal-plans/{id:\d+}/pdf', [$home, 'pdf']);

$router->get('/login', [$auth, 'loginForm']);
$router->post('/login', [$auth, 'login']);
$router->post('/logout', [$auth, 'logout']);

$router->get('/admin', [$admin, 'index']);

$router->get('/admin/meal-plans', [$mealPlans, 'index']);
$router->get('/admin/meal-plans/upload', [$mealPlans, 'create']);
$router->post('/admin/meal-plans/upload', [$mealPlans, 'store']);
$router->get('/admin/meal-plans/{id:\d+}', [$mealPlans, 'show']);
$router->get('/admin/meal-plans/{id:\d+}/preview', [$mealPlans, 'preview']);
$router->post('/admin/meal-plans/{id:\d+}/activate', [$mealPlans, 'activate']);
$router->post('/admin/meal-plans/{id:\d+}/archive', [$mealPlans, 'archive']);
$router->post('/admin/meal-plans/{id:\d+}/delete', [$mealPlans, 'delete']);
$router->get('/admin/meal-plans/{id:\d+}/download', [$mealPlans, 'download']);

$router->get('/admin/users', [$users, 'index']);
$router->get('/admin/users/create', [$users, 'create']);
$router->post('/admin/users/create', [$users, 'store']);
$router->get('/admin/users/{id:\d+}/edit', [$users, 'edit']);
$router->post('/admin/users/{id:\d+}/edit', [$users, 'update']);
$router->post('/admin/users/{id:\d+}/password', [$users, 'resetPassword']);
$router->post('/admin/users/{id:\d+}/toggle', [$users, 'toggle']);

$router->get('/admin/settings', [$settings, 'index']);
$router->post('/admin/settings', [$settings, 'update']);

$router->get('/admin/logs', [$logs, 'index']);

$router->get('/admin/update', [$updates, 'index']);
$router->post('/admin/update', [$updates, 'run']);

return $router;
