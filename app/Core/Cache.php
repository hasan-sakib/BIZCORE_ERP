<?php

declare(strict_types=1);

namespace App\Core;

use Predis\Client as RedisClient;

class Cache
{
    private RedisClient $redis;
    private string $prefix;
    private array $taggedKeys = [];

    public function __construct(private readonly array $config)
    {
        $this->redis = new RedisClient([
            'scheme' => 'tcp',
            'host'   => $config['host'] ?? '127.0.0.1',
            'port'   => $config['port'] ?? 6379,
            'password' => $config['password'] ?: null,
            'database' => $config['database'] ?? 0,
        ]);
        $this->prefix = $config['prefix'] ?? 'bizcore:';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($this->prefix . $key);
        if ($value === null) {
            return $default;
        }
        return $this->unserialize($value);
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $serialized = $this->serialize($value);
        if ($ttl > 0) {
            return $this->redis->setex($this->prefix . $key, $ttl, $serialized) === 'OK';
        }
        return $this->redis->set($this->prefix . $key, $serialized) === 'OK';
    }

    public function put(string $key, mixed $value, int $ttl = 3600): bool
    {
        return $this->set($key, $value, $ttl);
    }

    public function has(string $key): bool
    {
        return (bool) $this->redis->exists($this->prefix . $key);
    }

    public function forget(string $key): bool
    {
        return (bool) $this->redis->del([$this->prefix . $key]);
    }

    public function flush(): bool
    {
        $keys = $this->redis->keys($this->prefix . '*');
        if (!empty($keys)) {
            $this->redis->del($keys);
        }
        return true;
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);
        if ($value !== null) {
            return $value;
        }
        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function rememberForever(string $key, callable $callback): mixed
    {
        return $this->remember($key, 0, $callback);
    }

    public function increment(string $key, int $by = 1): int
    {
        return (int) $this->redis->incrby($this->prefix . $key, $by);
    }

    public function decrement(string $key, int $by = 1): int
    {
        return (int) $this->redis->decrby($this->prefix . $key, $by);
    }

    public function expire(string $key, int $ttl): bool
    {
        return (bool) $this->redis->expire($this->prefix . $key, $ttl);
    }

    public function ttl(string $key): int
    {
        return (int) $this->redis->ttl($this->prefix . $key);
    }

    public function push(string $key, mixed $value): void
    {
        $this->redis->rpush($this->prefix . $key, [$this->serialize($value)]);
    }

    public function pop(string $key): mixed
    {
        $value = $this->redis->lpop($this->prefix . $key);
        return $value !== null ? $this->unserialize($value) : null;
    }

    public function addToSet(string $key, mixed ...$values): void
    {
        $serialized = array_map([$this, 'serialize'], $values);
        $this->redis->sadd($this->prefix . $key, $serialized);
    }

    public function isMember(string $key, mixed $value): bool
    {
        return (bool) $this->redis->sismember($this->prefix . $key, $this->serialize($value));
    }

    public function getRedis(): RedisClient
    {
        return $this->redis;
    }

    private function serialize(mixed $value): string
    {
        return serialize($value);
    }

    private function unserialize(string $value): mixed
    {
        return unserialize($value);
    }
}
