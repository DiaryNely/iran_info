<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{methods: array<int, string>, path: string, handler: callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add(['GET'], $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add(['POST'], $path, $handler);
    }

    public function match(array $methods, string $path, callable $handler): void
    {
        $normalized = array_map(static fn (string $m): string => strtoupper($m), $methods);
        $this->add($normalized, $path, $handler);
    }

    private function add(array $methods, string $path, callable $handler): void
    {
        $this->routes[] = [
            'methods' => $methods,
            'path' => $this->normalizePath($path),
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $requestMethod = strtoupper($method);
        $requestPath = $this->normalizePath(parse_url($uri, PHP_URL_PATH) ?: '/');

        foreach ($this->routes as $route) {
            if (!in_array($requestMethod, $route['methods'], true)) {
                continue;
            }

            $params = [];
            if ($this->matches($route['path'], $requestPath, $params)) {
                ($route['handler'])($params);
                return;
            }
        }

        http_response_code(404);
        view('errors.404');
    }

    private function matches(string $routePath, string $requestPath, array &$params): bool
    {
        $quoted = preg_quote($routePath, '/');
        $pattern = preg_replace('/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}/', '(?P<$1>[^\\/]+)', $quoted);

        if ($pattern === null) {
            return false;
        }

        if (!preg_match('/^' . $pattern . '$/', $requestPath, $matches)) {
            return false;
        }

        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = urldecode($value);
            }
        }

        return true;
    }

    private function normalizePath(string $path): string
    {
        $clean = '/' . trim($path, '/');
        return $clean === '//' ? '/' : $clean;
    }
}
