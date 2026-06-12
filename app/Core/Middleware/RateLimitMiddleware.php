<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Cache;
use App\Http\Request;

class RateLimitMiddleware
{
    private array $limits = [
        '/api/v1/auth/login' => ['requests' => 5, 'window' => 300],
        '/api/'              => ['requests' => 60, 'window' => 60],
        'default'            => ['requests' => 120, 'window' => 60],
    ];

    public function __construct(private readonly Cache $cache) {}

    public function handle(Request $request, callable $next): mixed
    {
        $limit = $this->getLimit($request->path());
        $key = 'rate_limit:' . $request->ip() . ':' . md5($request->path());

        $current = $this->cache->increment($key);
        if ($current === 1) {
            $this->cache->expire($key, $limit['window']);
        }

        if ($current > $limit['requests']) {
            $retryAfter = $this->cache->ttl($key);
            http_response_code(429);
            header('Content-Type: application/json');
            header("Retry-After: {$retryAfter}");
            header("X-RateLimit-Limit: {$limit['requests']}");
            header("X-RateLimit-Remaining: 0");
            echo json_encode([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
            ]);
            exit;
        }

        header("X-RateLimit-Limit: {$limit['requests']}");
        header("X-RateLimit-Remaining: " . max(0, $limit['requests'] - $current));

        return $next();
    }

    private function getLimit(string $path): array
    {
        foreach ($this->limits as $prefix => $limit) {
            if ($prefix !== 'default' && str_starts_with($path, $prefix)) {
                return $limit;
            }
        }
        return $this->limits['default'];
    }
}
