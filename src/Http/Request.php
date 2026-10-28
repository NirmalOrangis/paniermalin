<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    /**
     * @param array<string, mixed>  $query
     * @param array<string, string> $cookies
     * @param array<string, string> $headers lower-cased header names
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $query = [],
        private readonly array $cookies = [],
        private readonly array $headers = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $path = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_') && is_string($value)) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
            }
        }

        return new self(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), $path, $_GET, $_COOKIE, $headers);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->query);
    }

    public function query(string $key, string $default = ''): string
    {
        $value = $this->query[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    public function queryInt(string $key, int $default): int
    {
        $value = $this->query($key);

        return ctype_digit($value) ? (int) $value : $default;
    }

    /**
     * Accepts both "a,b" and "key[]=a&key[]=b" forms.
     *
     * @return list<string>
     */
    public function queryList(string $key): array
    {
        $value = $this->query[$key] ?? [];
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        if (!is_array($value)) {
            return [];
        }

        $items = array_map(static fn ($v): string => is_string($v) ? trim($v) : '', $value);

        return array_values(array_unique(array_filter($items, static fn (string $v): bool => $v !== '')));
    }

    public function cookie(string $name): ?string
    {
        $value = $this->cookies[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
