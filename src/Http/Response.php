<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        private readonly string $body = '',
        private readonly int $status = 200,
        private array $headers = [],
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'private, no-cache',
        ]);
    }

    /** @param array<mixed> $data */
    public static function json(array $data, int $status = 200): self
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return new self($body, $status, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'private, max-age=60',
            'Vary' => 'Cookie',
        ]);
    }

    public static function xml(string $body): self
    {
        return new self($body, 200, ['Content-Type' => 'application/xml; charset=utf-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /** @param array<string, string> $headers */
    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->headers = $headers + $this->headers;

        return $clone;
    }

    /** Answers 304 Not Modified when the client already has this exact body. */
    public function withEtag(Request $request): self
    {
        $etag = '"' . md5($this->body) . '"';
        if ($request->header('if-none-match') === $etag) {
            return new self('', 304, ['ETag' => $etag] + array_intersect_key($this->headers, ['Cache-Control' => 1, 'Vary' => 1]));
        }

        return $this->withHeaders(['ETag' => $etag]);
    }

    public function send(bool $headOnly = false): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }
        if (!$headOnly) {
            echo $this->body;
        }
    }
}
