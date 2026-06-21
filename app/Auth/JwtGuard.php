<?php

declare(strict_types=1);

namespace App\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;
use Throwable;

class JwtGuard implements Guard
{
    use GuardHelpers;

    public function __construct(
        UserProvider $provider,
        private readonly Request $request,
        private readonly Cache   $cache,
    ) {
        $this->provider = $provider;
    }

    public function user(): mixed
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->getTokenFromRequest();

        if ($token === null) {
            return null;
        }

        try {
            $payload = JWT::decode($token, new Key(config('jwt.secret'), config('jwt.algo')));
        } catch (Throwable) {
            return null;
        }

        // Check blacklist
        if (config('jwt.blacklist') && $this->isBlacklisted($token)) {
            return null;
        }

        $userId = $payload->sub ?? null;

        if ($userId === null) {
            return null;
        }

        $this->user = $this->provider->retrieveById($userId);

        return $this->user;
    }

    public function validate(array $credentials = []): bool
    {
        return false; // stateless guard — validate via login endpoint
    }

    public function blacklist(string $token): void
    {
        $ttl = config('jwt.refresh_ttl', 20160) * 60; // convert minutes → seconds
        $this->cache->put($this->blacklistKey($token), true, $ttl);
    }

    public function issueToken(int $userId): string
    {
        $now = time();
        $payload = [
            'iss' => config('app.url'),
            'sub' => $userId,
            'iat' => $now,
            'exp' => $now + config('jwt.ttl', 60) * 60,
            'jti' => bin2hex(random_bytes(16)),
        ];

        return JWT::encode($payload, config('jwt.secret'), config('jwt.algo'));
    }

    public function issueRefreshToken(int $userId): string
    {
        $now = time();
        $payload = [
            'iss'  => config('app.url'),
            'sub'  => $userId,
            'iat'  => $now,
            'exp'  => $now + config('jwt.refresh_ttl', 20160) * 60,
            'type' => 'refresh',
            'jti'  => bin2hex(random_bytes(16)),
        ];

        return JWT::encode($payload, config('jwt.secret'), config('jwt.algo'));
    }

    private function getTokenFromRequest(): ?string
    {
        $header = $this->request->header('Authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }

    private function isBlacklisted(string $token): bool
    {
        return (bool) $this->cache->get($this->blacklistKey($token));
    }

    private function blacklistKey(string $token): string
    {
        return config('jwt.redis_blacklist_key') . ':' . hash('sha256', $token);
    }
}
