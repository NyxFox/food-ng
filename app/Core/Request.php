<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function __construct(
        private readonly array $query,
        private readonly array $request,
        private readonly array $files,
        private readonly array $server
    ) {
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_FILES, $_SERVER);
    }

    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function uriPath(): string
    {
        $uri = (string) ($this->server['REQUEST_URI'] ?? '/');
        $path = (string) parse_url($uri, PHP_URL_PATH);

        return $path === '' ? '/' : $path;
    }

    public function pathWithoutBase(string $basePath = ''): string
    {
        $path = $this->uriPath();
        $basePath = trim($basePath, '/');

        if ($basePath === '') {
            return $path;
        }

        $prefix = '/' . $basePath;

        if ($path === $prefix) {
            return '/';
        }

        if (str_starts_with($path, $prefix . '/')) {
            return substr($path, strlen($prefix)) ?: '/';
        }

        return $path;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->request;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return isset($this->files[$key]) ? $this->files[$key] : null;
    }

    public function ip(): ?string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $header) {
            if (!empty($this->server[$header])) {
                $value = (string) $this->server[$header];

                if ($header === 'HTTP_X_FORWARDED_FOR') {
                    return trim(explode(',', $value)[0]);
                }

                return $value;
            }
        }

        return null;
    }
}
