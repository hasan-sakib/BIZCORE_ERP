<?php

declare(strict_types=1);

namespace App\Core;

use App\Entities\User;
use App\Repositories\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth
{
    private ?User $resolvedUser = null;

    public function __construct(
        private readonly Session $session,
        private readonly UserRepository $userRepository,
        private readonly Cache $cache
    ) {}

    public function check(): bool
    {
        return $this->session->userId() !== null;
    }

    public function id(): ?int
    {
        return $this->session->userId();
    }

    public function user(): ?User
    {
        if ($this->resolvedUser !== null) {
            return $this->resolvedUser;
        }

        $userId = $this->id();
        if ($userId === null) {
            return null;
        }

        $this->resolvedUser = $this->cache->remember(
            "auth_user_{$userId}",
            300,
            fn() => $this->userRepository->findWithRole($userId)
        );

        return $this->resolvedUser;
    }

    public function loginById(int $userId): void
    {
        $this->session->regenerate(true);
        $this->session->setUserId($userId);
        $this->resolvedUser = null;
    }

    public function logout(): void
    {
        $userId = $this->id();
        if ($userId) {
            $this->cache->forget("auth_user_{$userId}");
            $this->cache->forget("user_permissions_{$userId}");
        }
        $this->session->destroy();
        $this->resolvedUser = null;
    }

    public function generateJWT(User $user): string
    {
        $config = config('jwt');
        $payload = [
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'iat' => time(),
            'exp' => time() + ($config['ttl'] * 60),
            'sub' => $user->id,
            'email' => $user->email,
            'role_id' => $user->roleId,
            'branch_id' => $user->branchId,
        ];

        return JWT::encode($payload, $config['secret'], $config['algo'] ?? 'HS256');
    }

    public function generateRefreshToken(User $user): string
    {
        $config = config('jwt');
        $payload = [
            'iss' => config('app.url'),
            'iat' => time(),
            'exp' => time() + ($config['refresh_ttl'] * 60),
            'sub' => $user->id,
            'type' => 'refresh',
        ];

        return JWT::encode($payload, $config['secret'], $config['algo'] ?? 'HS256');
    }

    public function validateJWT(string $token): ?object
    {
        try {
            $config = config('jwt');

            if ($this->cache->isMember('jwt_blacklist', $token)) {
                return null;
            }

            return JWT::decode($token, new Key($config['secret'], $config['algo'] ?? 'HS256'));
        } catch (\Throwable) {
            return null;
        }
    }

    public function blacklistToken(string $token): void
    {
        $payload = $this->validateJWT($token);
        if ($payload) {
            $ttl = $payload->exp - time();
            if ($ttl > 0) {
                $this->cache->addToSet('jwt_blacklist', $token);
                $this->cache->expire('jwt_blacklist', $ttl + 60);
            }
        }
    }

    public function getUserFromJWT(string $token): ?User
    {
        $payload = $this->validateJWT($token);
        if (!$payload) {
            return null;
        }

        return $this->cache->remember(
            "auth_user_{$payload->sub}",
            300,
            fn() => $this->userRepository->findById((int)$payload->sub)
        );
    }
}
