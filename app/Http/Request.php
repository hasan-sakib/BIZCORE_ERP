<?php

declare(strict_types=1);

namespace App\Http;

/**
 * HTTP Request abstraction.
 *
 * Wraps PHP superglobals into a clean, testable object.
 */
final class Request
{
    /** @var array<string, mixed> */
    private readonly array $query;

    /** @var array<string, mixed> */
    private readonly array $post;

    /** @var array<string, mixed> */
    private readonly array $server;

    /** @var array<string, mixed> */
    private readonly array $cookies;

    /** @var array<string, array<string, mixed>> */
    private readonly array $files;

    /** @var array<string, mixed> */
    private array $attributes = [];

    /** @var array<string, string> */
    private readonly array $headers;

    private readonly string $body;

    public function __construct(
        array $query   = [],
        array $post    = [],
        array $server  = [],
        array $cookies = [],
        array $files   = [],
        string $body   = '',
    ) {
        $this->query   = $query;
        $this->post    = $post;
        $this->server  = $server;
        $this->cookies = $cookies;
        $this->files   = $files;
        $this->body    = $body;
        $this->headers = $this->parseHeaders($server);
    }

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * Build from PHP superglobals.
     */
    public static function fromGlobals(): self
    {
        $body = file_get_contents('php://input') ?: '';

        return new self(
            query:   $_GET    ?? [],
            post:    $_POST   ?? [],
            server:  $_SERVER ?? [],
            cookies: $_COOKIE ?? [],
            files:   $_FILES  ?? [],
            body:    $body,
        );
    }

    // -------------------------------------------------------------------------
    // Input accessors
    // -------------------------------------------------------------------------

    /**
     * Get a value from POST or GET (POST wins), with an optional default.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Get all POST + GET merged (POST wins on collision).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    /**
     * Get only the specified keys from the request input.
     *
     * @param  string[]             $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    /**
     * Get all input except the specified keys.
     *
     * @param  string[]             $keys
     * @return array<string, mixed>
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    /**
     * Get a POST value.
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get a query-string value.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get an uploaded file descriptor.
     *
     * @return array<string, mixed>|null
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get a cookie value.
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    // -------------------------------------------------------------------------
    // Request metadata
    // -------------------------------------------------------------------------

    /**
     * Returns the HTTP method in upper-case (e.g. 'GET', 'POST').
     */
    public function method(): string
    {
        $method  = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        $override = strtoupper($this->server['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '');

        if ($method === 'POST' && in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
            return $override;
        }

        return $method;
    }

    /**
     * Returns true when the method matches the given value (case-insensitive).
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method();
    }

    /**
     * Returns the request URI path (without query string).
     */
    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return parse_url($uri, PHP_URL_PATH) ?? '/';
    }

    /**
     * Returns the client IP address, respecting common proxy headers.
     */
    public function ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($this->server[$key])) {
                // X-Forwarded-For can be a comma-separated list; take the first.
                $ip = trim(explode(',', $this->server[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Returns the User-Agent string.
     */
    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Returns true when the request was made over HTTPS.
     */
    public function isSecure(): bool
    {
        return ($this->server['HTTPS'] ?? '') === 'on'
            || ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    /**
     * Returns true when the request was made via XMLHttpRequest / fetch.
     */
    public function isAjax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    /**
     * Returns true when the Accept header prefers JSON.
     */
    public function wantsJson(): bool
    {
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json');
    }

    // -------------------------------------------------------------------------
    // CSRF
    // -------------------------------------------------------------------------

    /**
     * Returns the CSRF token from the request (header or POST field).
     */
    public function csrfToken(): string
    {
        return (string) ($this->headers['x-csrf-token']
            ?? $this->headers['x-xsrf-token']
            ?? $this->post['_token']
            ?? '');
    }

    // -------------------------------------------------------------------------
    // Route attributes (set by the router)
    // -------------------------------------------------------------------------

    /**
     * Set a named route attribute (e.g. a URL segment parsed by the router).
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get a named route attribute.
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Parse HTTP_* SERVER keys into a normalised lowercase header map.
     *
     * @param  array<string, mixed>    $server
     * @return array<string, string>
     */
    private function parseHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name           = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
            }
        }
        return $headers;
    }

    /**
     * Get a request header value by (case-insensitive) name.
     */
    public function header(string $name, string $default = ''): string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }
}
