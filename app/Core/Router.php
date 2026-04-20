<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /**
     * @var array<int, array{method:string, pattern:string, handler:callable}>
     */
    private array $routes = [];

    public function __construct(private readonly string $basePath = '')
    {
    }

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function dispatch(Request $request): void
    {
        $path = $request->pathWithoutBase($this->basePath);
        $method = $request->method() === 'HEAD' ? 'GET' : $request->method();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->compilePattern($route['pattern']);

            if (preg_match($pattern, $path, $matches) !== 1) {
                continue;
            }

            $parameters = [];

            foreach ($matches as $key => $value) {
                if (!is_int($key)) {
                    $parameters[$key] = $value;
                }
            }

            call_user_func($route['handler'], $request, $parameters);

            return;
        }

        throw new HttpException(404, 'Die angeforderte Seite wurde nicht gefunden.');
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    private function compilePattern(string $pattern): string
    {
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(:([^}]+))?\}/',
            static function (array $matches): string {
                $name = $matches[1];
                $constraint = $matches[3] ?? '[^/]+';

                return '(?P<' . $name . '>' . $constraint . ')';
            },
            $pattern
        );

        return '#^' . $pattern . '$#';
    }
}
