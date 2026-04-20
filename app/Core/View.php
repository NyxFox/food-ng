<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public function __construct(private readonly string $viewsPath)
    {
    }

    public function render(string $template, array $data = [], string $layout = 'main'): string
    {
        $templatePath = $this->resolvePath($template);

        extract($data, EXTR_SKIP);

        ob_start();
        require $templatePath;
        $content = (string) ob_get_clean();

        if ($layout === '') {
            return $content;
        }

        $layoutPath = $this->resolvePath('layouts/' . $layout);

        ob_start();
        require $layoutPath;

        return (string) ob_get_clean();
    }

    private function resolvePath(string $template): string
    {
        $path = rtrim($this->viewsPath, '/') . '/' . trim($template, '/') . '.php';

        if (!is_file($path)) {
            throw new RuntimeException('View not found: ' . $template);
        }

        return $path;
    }
}
