<?php

declare(strict_types=1);

$root = dirname(__DIR__);

return [
    'app' => [
        'name' => 'Food NG',
        'base_path' => '',
        'timezone' => 'Europe/Berlin',
        'debug' => false,
    ],
    'paths' => [
        'root' => $root,
        'storage' => $root . '/storage',
        'database' => $root . '/storage/database.sqlite',
        'log_file' => $root . '/storage/logs/app.log',
    ],
    'security' => [
        'session_name' => 'foodng_session',
        'csrf_field' => '_csrf',
        'max_upload_bytes' => 10 * 1024 * 1024,
        'max_branding_upload_bytes' => 2 * 1024 * 1024,
    ],
    'features' => [
        'update_runner' => true,
    ],
    'commands' => [
        'qpdf' => getenv('FOODNG_QPDF') ?: null,
        'soffice' => getenv('FOODNG_SOFFICE') ?: null,
        'git' => getenv('FOODNG_GIT') ?: null,
    ],
    'updates' => [
        'repo_path' => $root,
        'branch' => getenv('FOODNG_UPDATE_BRANCH') ?: 'main',
    ],
    'setup' => [
        'auto_create_default_admin' => true,
        'default_admin_username' => 'admin',
        'default_admin_display_name' => 'Administrator',
        'default_admin_password' => 'change-me-now!',
        'default_admin_role' => 'admin',
    ],
    'defaults' => [
        'settings' => [
            'site_title' => 'Food-NG',
            'site_subtitle' => 'CJD Dortmund',
            'banner_enabled' => '0',
            'banner_style' => 'info',
            'banner_text' => '',
            'theme_mode' => 'light',
            'accent_color' => '#0f766e',
            'github_url' => 'https://github.com/NyxFox/food-ng',
            'app_icon_path' => '',
            'app_icon_mime' => '',
            'app_icon_updated_at' => '',
            'imprint_text' => "Bitte Impressum in den Einstellungen pflegen.\n\nVerantwortlich:\nName / Firma\nStraße\nPLZ Ort",
            'privacy_text' => "Bitte Datenschutzhinweise in den Einstellungen pflegen.\n\nHier sollten Angaben zur Verarbeitung personenbezogener Daten stehen.",
        ],
    ],
];
