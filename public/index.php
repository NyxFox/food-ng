<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $assetPath = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    if (is_file($assetPath)) {
        return false;
    }
}

try {
    $container = require __DIR__ . '/../app/bootstrap.php';
    $router = require __DIR__ . '/../app/routes.php';
    $router->dispatch($container['request']);
} catch (\App\Core\HttpException $exception) {
    http_response_code($exception->statusCode());

    echo $container['view']->render('error', [
        'pageTitle' => 'Fehler ' . $exception->statusCode(),
        'statusCode' => $exception->statusCode(),
        'message' => $exception->getMessage(),
        'settings' => $container['settings']->all(),
        'currentUser' => $container['auth']->user(),
        'flashMessages' => $container['flash']->consume(),
        'oldInput' => $container['flash']->consumeOldInput(),
        'csrfToken' => $container['csrf']->token(),
        'currentPath' => $container['request']->uriPath(),
    ]);
} catch (\Throwable $exception) {
    http_response_code(500);

    if (isset($container) && is_array($container)) {
        $container['logger']->log(
            'unhandled_exception',
            'Unbehandelte Ausnahme aufgetreten.',
            [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ],
            $container['auth']->id(),
            'error',
            $container['request']->ip()
        );

        echo $container['view']->render('error', [
            'pageTitle' => 'Serverfehler',
            'statusCode' => 500,
            'message' => app_config('app.debug', false)
                ? $exception->getMessage()
                : 'Beim Verarbeiten der Anfrage ist ein Fehler aufgetreten.',
            'settings' => $container['settings']->all(),
            'currentUser' => $container['auth']->user(),
            'flashMessages' => $container['flash']->consume(),
            'oldInput' => $container['flash']->consumeOldInput(),
            'csrfToken' => $container['csrf']->token(),
            'currentPath' => $container['request']->uriPath(),
        ]);

        return;
    }

    echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Serverfehler</title>';
    echo '<body style="font-family: sans-serif; padding: 2rem; line-height: 1.5">';
    echo '<h1>Serverfehler</h1>';
    echo '<p>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
    echo '</body></html>';
}
