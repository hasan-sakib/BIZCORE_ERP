<?php

declare(strict_types=1);

namespace App\Security;

use Predis\Client as RedisClient;

class RateLimiter
{
    public function __construct(
        private readonly RedisClient $redis,
        private readonly int         $maxRequests,
        private readonly int         $decaySeconds,
        private readonly string      $prefix = '',
    ) {}

    public function attempt(string $key): array
    {
        $full    = $this->prefix . $key;
        $current = (int) $this->redis->incr($full);

        if ($current === 1) {
            $this->redis->expire($full, $this->decaySeconds);
        }

        $ttl = max(0, (int) $this->redis->ttl($full));

        return [
            'limit'       => $this->maxRequests,
            'remaining'   => max(0, $this->maxRequests - $current),
            'reset_at'    => time() + $ttl,
            'exceeded'    => $current > $this->maxRequests,
            'retry_after' => $ttl,
        ];
    }

    public function tooManyAttempts(string $key): bool
    {
        return (int) $this->redis->get($this->prefix . $key) >= $this->maxRequests;
    }

    public function clear(string $key): void
    {
        $this->redis->del([$this->prefix . $key]);
    }
}
