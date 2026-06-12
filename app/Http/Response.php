<?php

declare(strict_types=1);

namespace App\Http;

/**
 * HTTP Response abstraction.
 *
 * Thin wrapper for building and sending HTTP responses.
 * Controllers return Response instances; the front controller calls send().
 */
final class Response
{
    /** @var array<string, string> */
    private array $headers = [];

    public function __construct(
        private string $body       = '',
        private int    $statusCode = 200,
    ) {}

    // -------------------------------------------------------------------------
    // Static factories
    // -------------------------------------------------------------------------

    /**
     * Create a plain text / HTML response.
     */
    public static function make(string $body = '', int $status = 200): self
    {
        $response = new self($body, $status);
        $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
        return $response;
    }

    /**
     * Create a JSON response from any serialisable value.
     */
    public static function json(mixed $data, int $status = 200): self
    {
        $body     = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $response = new self($body, $status);
        $response->withHeader('Content-Type', 'application/json; charset=UTF-8');
        return $response;
    }

    /**
     * Create a redirect response.
     */
    public static function redirect(string $url, int $status = 302): self
    {
        $response = new self('', $status);
        $response->withHeader('Location', $url);
        return $response;
    }

    /**
     * Create an empty 204 No Content response.
     */
    public static function noContent(): self
    {
        return new self('', 204);
    }

    // -------------------------------------------------------------------------
    // Fluent mutators
    // -------------------------------------------------------------------------

    /**
     * Set or replace a header (chainable).
     */
    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set multiple headers at once (chainable).
     *
     * @param  array<string, string>  $headers
     */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->withHeader($name, $value);
        }
        return $this;
    }

    /**
     * Override the HTTP status code (chainable).
     */
    public function withStatus(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Set a cookie on the response (chainable).
     *
     * Uses the modern SameSite attribute to mitigate CSRF on cross-site requests.
     */
    public function withCookie(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = true,
        bool $httpOnly = true,
        string $sameSite = 'Lax',
    ): self {
        $cookieParts = ["{$name}=" . rawurlencode($value)];

        if ($expires > 0) {
            $cookieParts[] = 'Expires=' . gmdate('D, d M Y H:i:s T', $expires);
            $cookieParts[] = 'Max-Age=' . ($expires - time());
        }

        $cookieParts[] = "Path={$path}";

        if ($domain !== '') {
            $cookieParts[] = "Domain={$domain}";
        }

        if ($secure) {
            $cookieParts[] = 'Secure';
        }

        if ($httpOnly) {
            $cookieParts[] = 'HttpOnly';
        }

        $cookieParts[] = "SameSite={$sameSite}";

        // Multiple Set-Cookie headers must each be sent separately.
        // We accumulate them under a unique key.
        $cookieKey = 'Set-Cookie:' . $name;
        $this->headers[$cookieKey] = implode('; ', $cookieParts);

        return $this;
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    // -------------------------------------------------------------------------
    // Emission
    // -------------------------------------------------------------------------

    /**
     * Send the response to the PHP output buffer.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                // Strip internal keys used for cookie accumulation.
                $headerName = str_starts_with($name, 'Set-Cookie:') ? 'Set-Cookie' : $name;
                header("{$headerName}: {$value}", false);
            }
        }

        echo $this->body;
    }
}
