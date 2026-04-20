<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class BrandingService
{
    private const ALLOWED_MIME_TO_EXTENSION = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
    ];

    public function __construct(
        private readonly array $config,
        private readonly SettingsService $settings
    ) {
    }

    public function currentIcon(): ?array
    {
        $icon = $this->currentIconData();

        if ($icon === null) {
            return null;
        }

        $extension = strtolower((string) pathinfo($icon['absolute_path'], PATHINFO_EXTENSION));

        return [
            'relative_path' => $icon['relative_path'],
            'absolute_path' => $icon['absolute_path'],
            'mime' => $icon['mime'],
            'extension' => $extension !== '' ? $extension : self::ALLOWED_MIME_TO_EXTENSION[$icon['mime']],
            'updated_at' => $icon['updated_at'],
            'url' => $this->buildIconUrl($icon['updated_at']),
        ];
    }

    public function iconUrl(): ?string
    {
        $icon = $this->currentIconData();

        return $icon === null ? null : $this->buildIconUrl($icon['updated_at']);
    }

    public function iconMime(): ?string
    {
        $icon = $this->currentIconData();

        return $icon['mime'] ?? null;
    }

    public function replaceIcon(array $file, ?int $userId = null): array
    {
        $meta = $this->inspectUpload($file);
        $directory = rtrim((string) $this->config['paths']['storage'], '/') . '/uploads/branding';

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = 'app-icon-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $meta['extension'];
        $targetPath = $directory . '/' . $filename;
        $current = $this->currentIconData();

        $this->moveUpload($file, $targetPath);

        try {
            $this->settings->save([
                'app_icon_path' => $this->relativeStoragePath($targetPath),
                'app_icon_mime' => $meta['mime'],
                'app_icon_updated_at' => date('c'),
            ], $userId);
        } catch (\Throwable $exception) {
            @unlink($targetPath);
            throw $exception;
        }

        if ($current !== null && is_file($current['absolute_path']) && $current['absolute_path'] !== $targetPath) {
            @unlink($current['absolute_path']);
        }

        return $this->currentIcon() ?? throw new RuntimeException('Das App-Icon konnte nach dem Speichern nicht geladen werden.');
    }

    public function removeIcon(?int $userId = null): bool
    {
        $relativePath = trim((string) $this->settings->get('app_icon_path', ''));

        if ($relativePath === '') {
            return false;
        }

        $absolutePath = $this->absoluteStoragePath($relativePath);

        $this->settings->save([
            'app_icon_path' => '',
            'app_icon_mime' => '',
            'app_icon_updated_at' => '',
        ], $userId);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        return true;
    }

    private function currentIconData(): ?array
    {
        $relativePath = trim((string) $this->settings->get('app_icon_path', ''));
        $mime = trim((string) $this->settings->get('app_icon_mime', ''));
        $updatedAt = trim((string) $this->settings->get('app_icon_updated_at', ''));

        if ($relativePath === '' || $mime === '') {
            return null;
        }

        $absolutePath = $this->absoluteStoragePath($relativePath);

        if (!is_file($absolutePath) || !isset(self::ALLOWED_MIME_TO_EXTENSION[$mime])) {
            return null;
        }

        return [
            'relative_path' => $relativePath,
            'absolute_path' => $absolutePath,
            'mime' => $mime,
            'updated_at' => $updatedAt !== '' ? $updatedAt : date('c', (int) filemtime($absolutePath)),
        ];
    }

    private function inspectUpload(array $file): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Das App-Icon ist zu groß.',
                UPLOAD_ERR_PARTIAL => 'Das App-Icon wurde nur teilweise hochgeladen.',
                UPLOAD_ERR_NO_FILE => 'Bitte eine Bilddatei auswählen.',
                default => 'App-Icon-Upload fehlgeschlagen.',
            });
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        $maxUploadBytes = (int) ($this->config['security']['max_branding_upload_bytes'] ?? 2097152);

        if ($size <= 0 || $size > $maxUploadBytes) {
            throw new RuntimeException('Das App-Icon darf maximal ' . number_format($maxUploadBytes / 1024 / 1024, 1, ',', '.') . ' MB groß sein.');
        }

        $originalName = (string) ($file['name'] ?? 'app-icon');
        $originalExtension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = (string) ((new \finfo(FILEINFO_MIME_TYPE))->file($tmpPath) ?: 'application/octet-stream');

        if ($originalExtension === 'ico' && $mime === 'application/octet-stream' && $this->looksLikeIco($tmpPath)) {
            $mime = 'image/x-icon';
        }

        $extension = self::ALLOWED_MIME_TO_EXTENSION[$mime] ?? null;

        if ($extension === null) {
            throw new RuntimeException('Erlaubt sind PNG, JPG, WEBP oder ICO als App-Icon.');
        }

        return [
            'mime' => $mime,
            'extension' => $extension,
        ];
    }

    private function moveUpload(array $file, string $targetPath): void
    {
        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $moved = move_uploaded_file($tmpPath, $targetPath);

        if (!$moved) {
            $moved = @rename($tmpPath, $targetPath) || @copy($tmpPath, $targetPath);
        }

        if (!$moved) {
            throw new RuntimeException('Das App-Icon konnte nicht gespeichert werden.');
        }
    }

    private function looksLikeIco(string $path): bool
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 4);
        fclose($handle);

        return $header === "\x00\x00\x01\x00";
    }

    private function absoluteStoragePath(string $relativePath): string
    {
        return rtrim((string) $this->config['paths']['storage'], '/') . '/' . ltrim($relativePath, '/');
    }

    private function relativeStoragePath(string $absolutePath): string
    {
        $storageRoot = rtrim((string) $this->config['paths']['storage'], '/');
        $relative = ltrim(str_replace($storageRoot, '', $absolutePath), '/');

        return str_replace('\\', '/', $relative);
    }

    private function buildIconUrl(string $updatedAt): string
    {
        $url = url('branding/app-icon');

        return $updatedAt === '' ? $url : $url . '?v=' . rawurlencode($updatedAt);
    }
}
