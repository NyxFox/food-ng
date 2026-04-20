<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\AuthService;
use App\Services\BrandingService;
use App\Services\CsrfService;
use App\Services\Database;
use App\Services\DocumentProcessor;
use App\Services\FlashService;
use App\Services\LoggerService;
use App\Services\MealPlanService;
use App\Services\SettingsService;
use App\Services\UpdateService;
use App\Services\UserService;

abstract class BaseController
{
    public function __construct(protected readonly array $container)
    {
    }

    protected function render(string $template, array $data = [], string $layout = 'main'): void
    {
        $shared = [
            'settings' => $this->settings()->all(),
            'currentUser' => $this->auth()->user(),
            'flashMessages' => $this->flash()->consume(),
            'oldInput' => $this->flash()->consumeOldInput(),
            'csrfToken' => $this->csrf()->token(),
            'currentPath' => $this->request()->uriPath(),
            'pageTitle' => $data['pageTitle'] ?? app_config('app.name', 'Food NG'),
            'appVersion' => app_config('app.version', '0.0.0'),
            'brandingIconUrl' => $this->branding()->iconUrl(),
            'brandingIconMime' => $this->branding()->iconMime(),
        ];

        echo $this->view()->render($template, array_merge($shared, $data), $layout);
    }

    protected function redirect(string $path, int $statusCode = 302): never
    {
        header('Location: ' . url($path), true, $statusCode);
        exit;
    }

    protected function requireLogin(): array
    {
        $user = $this->auth()->user();

        if ($user === null) {
            $this->flash()->error('Bitte zuerst anmelden.');
            $this->redirect('login');
        }

        return $user;
    }

    protected function requireAdmin(): array
    {
        $user = $this->requireLogin();

        if (($user['role'] ?? null) !== 'admin') {
            throw new HttpException(403, 'Für diesen Bereich werden Administratorrechte benötigt.');
        }

        return $user;
    }

    protected function requireValidCsrf(): void
    {
        $fieldName = (string) app_config('security.csrf_field', '_csrf');
        $token = (string) $this->request()->input($fieldName, '');

        if (!$this->csrf()->validate($token)) {
            throw new HttpException(419, 'Sicherheitsprüfung fehlgeschlagen. Bitte Formular neu laden und erneut absenden.');
        }
    }

    protected function request(): Request
    {
        return $this->container['request'];
    }

    protected function view(): View
    {
        return $this->container['view'];
    }

    protected function auth(): AuthService
    {
        return $this->container['auth'];
    }

    protected function flash(): FlashService
    {
        return $this->container['flash'];
    }

    protected function csrf(): CsrfService
    {
        return $this->container['csrf'];
    }

    protected function logger(): LoggerService
    {
        return $this->container['logger'];
    }

    protected function settings(): SettingsService
    {
        return $this->container['settings'];
    }

    protected function branding(): BrandingService
    {
        return $this->container['branding'];
    }

    protected function mealPlans(): MealPlanService
    {
        return $this->container['mealPlans'];
    }

    protected function users(): UserService
    {
        return $this->container['users'];
    }

    protected function documentProcessor(): DocumentProcessor
    {
        return $this->container['documentProcessor'];
    }

    protected function updater(): UpdateService
    {
        return $this->container['updater'];
    }

    protected function db(): Database
    {
        return $this->container['db'];
    }
}
