<?php

declare(strict_types=1);

function app_config(?string $key = null, mixed $default = null): mixed
{
    $config = $GLOBALS['app_config'] ?? [];

    if ($key === null) {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }

        $value = $value[$segment];
    }

    return $value;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $basePath = trim((string) app_config('app.base_path', ''), '/');
    $base = $basePath === '' ? '' : '/' . $basePath;
    $normalizedPath = trim($path, '/');

    if ($normalizedPath === '') {
        return $base === '' ? '/' : $base;
    }

    return $base . '/' . $normalizedPath;
}

function asset_url(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function csrf_field(string $token): string
{
    $fieldName = (string) app_config('security.csrf_field', '_csrf');

    return '<input type="hidden" name="' . e($fieldName) . '" value="' . e($token) . '">';
}

function old(array $oldInput, string $key, string $default = ''): string
{
    return (string) ($oldInput[$key] ?? $default);
}

function format_datetime(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '—';
    }

    try {
        $date = new DateTimeImmutable($value);
    } catch (Throwable) {
        return $value;
    }

    return $date->format('d.m.Y H:i');
}

function bool_setting(array $settings, string $key): bool
{
    return ($settings[$key] ?? '0') === '1';
}

function nl2br_safe(?string $value): string
{
    return nl2br(e($value), false);
}

function current_path_starts_with(string $currentPath, string $path): bool
{
    $current = rtrim($currentPath, '/');
    $expected = rtrim(url($path), '/');

    if ($current === '') {
        $current = '/';
    }

    if ($expected === '') {
        $expected = '/';
    }

    return $current === $expected || str_starts_with($current . '/', $expected . '/');
}

function status_badge_class(string $status): string
{
    return match ($status) {
        'active', 'approved', 'info' => 'success',
        'draft', 'pending', 'warning' => 'warning',
        'error' => 'error',
        'archived', 'rejected' => 'muted',
        default => 'muted',
    };
}

function source_type_label(?string $value): string
{
    return strtoupper((string) $value);
}
