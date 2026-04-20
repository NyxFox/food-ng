<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\View;
use App\Services\AuthService;
use App\Services\BrandingService;
use App\Services\CommandRunner;
use App\Services\CsrfService;
use App\Services\Database;
use App\Services\DocumentProcessor;
use App\Services\FlashService;
use App\Services\LoggerService;
use App\Services\MealPlanService;
use App\Services\Migrator;
use App\Services\SettingsService;
use App\Services\UpdateService;
use App\Services\UserService;
use App\Services\VersionService;

$configExamplePath = __DIR__ . '/../config/app.example.php';
$configOverridePath = __DIR__ . '/../config/app.php';

$config = require $configExamplePath;

if (!is_array($config)) {
    throw new RuntimeException('Die Basis-Konfiguration in config/app.example.php muss ein Array zurueckgeben.');
}

if (is_file($configOverridePath)) {
    $configOverride = require $configOverridePath;

    if (!is_array($configOverride)) {
        throw new RuntimeException('Die lokale Konfiguration in config/app.php muss ein Array zurueckgeben.');
    }

    $config = array_replace_recursive($config, $configOverride);
}

date_default_timezone_set((string) ($config['app']['timezone'] ?? 'UTC'));
error_reporting(E_ALL);
ini_set('display_errors', ($config['app']['debug'] ?? false) ? '1' : '0');

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$sessionName = (string) ($config['security']['session_name'] ?? 'foodng_session');

session_name($sessionName);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

require __DIR__ . '/Helpers/functions.php';

$versionService = new VersionService((string) ($config['paths']['root'] ?? dirname(__DIR__)));
$config['app']['version'] = $versionService->current();
$GLOBALS['app_config'] = $config;

foreach ([
    $config['paths']['storage'],
    $config['paths']['storage'] . '/uploads',
    $config['paths']['storage'] . '/generated',
    $config['paths']['storage'] . '/logs',
    $config['paths']['storage'] . '/cache',
] as $directory) {
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

$database = new Database((string) $config['paths']['database']);
$migrator = new Migrator($database, $config);
$migrator->migrate();

$request = Request::capture();
$view = new View(__DIR__ . '/Views');
$flash = new FlashService();
$logger = new LoggerService($database, (string) $config['paths']['log_file']);
$csrf = new CsrfService((string) ($config['security']['csrf_field'] ?? '_csrf'));
$commandRunner = new CommandRunner($config);
$settings = new SettingsService($database, $config);
$branding = new BrandingService($config, $settings);
$auth = new AuthService($database, $logger, $flash);
$documentProcessor = new DocumentProcessor($config, $commandRunner);
$mealPlans = new MealPlanService($database, $logger, $documentProcessor, $config);
$users = new UserService($database, $logger);
$updater = new UpdateService($config, $commandRunner, $logger, $versionService);

return [
    'config' => $config,
    'request' => $request,
    'view' => $view,
    'flash' => $flash,
    'logger' => $logger,
    'csrf' => $csrf,
    'settings' => $settings,
    'branding' => $branding,
    'auth' => $auth,
    'documentProcessor' => $documentProcessor,
    'mealPlans' => $mealPlans,
    'users' => $users,
    'updater' => $updater,
    'version' => $versionService,
    'db' => $database,
];
