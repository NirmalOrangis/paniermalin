<?php

declare(strict_types=1);

namespace App\Http;

final class Router
{
    /** @var list<array{string, string, callable}> */
    private array $routes = [];

    /** Registers a GET route; "{name}" segments become named handler arguments. */
    public function get(string $pattern, callable $handler): self
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[a-z0-9-]+)', $pattern) . '$#';
        $this->routes[] = ['GET', $regex, $handler];

        return $this;
    }

    /** @return array{callable, array<string, string>}|null */
    public function match(Request $request): ?array
    {
        $method = $request->method === 'HEAD' ? 'GET' : $request->method;
        foreach ($this->routes as [$routeMethod, $regex, $handler]) {
            if ($routeMethod === $method && preg_match($regex, $request->path, $matches)) {
                return [$handler, array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY)];
            }
        }

        return null;
    }
}
