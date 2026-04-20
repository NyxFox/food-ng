<?php

declare(strict_types=1);

namespace App\Services;

final class VersionService
{
    private const FALLBACK_VERSION = '0.0.0';

    public function __construct(private readonly string $rootPath)
    {
    }

    public function current(): string
    {
        return $this->readFromPackageJson($this->packageFile()) ?? self::FALLBACK_VERSION;
    }

    public function fromPackageJsonContents(string $contents): ?string
    {
        if (trim($contents) === '') {
            return null;
        }

        $decoded = json_decode($contents, true);

        if (!is_array($decoded) || !is_string($decoded['version'] ?? null)) {
            return null;
        }

        $version = trim($decoded['version']);

        return $this->isSemanticVersion($version) ? $version : null;
    }

    public function packageFile(): string
    {
        return rtrim($this->rootPath, '/') . '/package.json';
    }

    private function readFromPackageJson(string $path): ?string
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if (!is_string($contents) || trim($contents) === '') {
            return null;
        }

        return $this->fromPackageJsonContents($contents);
    }

    private function isSemanticVersion(string $version): bool
    {
        return preg_match(
            '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?(?:\+[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?$/',
            $version
        ) === 1;
    }
}
