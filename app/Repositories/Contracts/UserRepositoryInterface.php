<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Entities\User;
use App\Repositories\PaginatedResult;
use DateTime;

/**
 * Contract for the User repository.
 */
interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findWithRole(int $id): ?User;

    /**
     * @param  array<string, mixed> $filters
     */
    public function findByBranch(int $branchId, array $filters, int $page, int $perPage): PaginatedResult;

    /**
     * @param  array<string, mixed> $data
     */
    public function create(array $data): int;

    /**
     * @param  array<string, mixed> $data
     */
    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function updateLoginAttempts(int $userId, int $attempts): void;

    public function lockUser(int $userId, DateTime $until): void;

    public function unlockUser(int $userId): void;

    public function updateLastLogin(int $userId): void;

    public function savePasswordHistory(int $userId, string $hash): void;

    /**
     * @return string[]
     */
    public function getPasswordHistory(int $userId, int $limit = 5): array;

    public function logLoginAttempt(
        int $userId,
        string $ip,
        string $ua,
        string $status,
        ?string $reason,
    ): void;

    /**
     * @param  array<string, mixed> $filters
     */
    public function getAllWithFilters(array $filters, int $page, int $perPage): PaginatedResult;
}
