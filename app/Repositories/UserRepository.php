<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Entities\User;
use App\Entities\UserStatus;
use App\Repositories\Contracts\UserRepositoryInterface;
use DateTime;

/**
 * UserRepository
 *
 * All SQL queries related to the `users` table and its satellite tables
 * (login_history, password_history) live here. Business logic MUST NOT
 * appear in this class.
 */
final class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    // -------------------------------------------------------------------------
    // Reads
    // -------------------------------------------------------------------------

    /**
     * Find a user by primary key.
     */
    public function findById(int $id): ?User
    {
        $row = $this->fetchOne(
            'SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id],
        );

        return $row !== null ? User::fromArray($row) : null;
    }

    /**
     * Find a user by their email address (case-insensitive).
     */
    public function findByEmail(string $email): ?User
    {
        $row = $this->fetchOne(
            'SELECT * FROM users WHERE LOWER(email) = :email AND deleted_at IS NULL LIMIT 1',
            [':email' => strtolower($email)],
        );

        return $row !== null ? User::fromArray($row) : null;
    }

    /**
     * Find a user together with their role (eager-loaded into the array).
     * The caller may hydrate both entities from the merged row.
     */
    public function findWithRole(int $id): ?User
    {
        $row = $this->fetchOne(
            <<<SQL
            SELECT
                u.*,
                r.name        AS role_name,
                r.slug        AS role_slug,
                r.description AS role_description,
                r.permissions AS role_permissions,
                r.is_system   AS role_is_system
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE u.id = :id
              AND u.deleted_at IS NULL
            LIMIT 1
            SQL,
            [':id' => $id],
        );

        return $row !== null ? User::fromArray($row) : null;
    }

    /**
     * Paginated list of users scoped to a specific branch with optional filters.
     *
     * @param  array<string, mixed> $filters  Supported keys: search, status, role_id.
     */
    public function findByBranch(int $branchId, array $filters, int $page, int $perPage): PaginatedResult
    {
        ['limit' => $limit, 'offset' => $offset] = $this->limitOffset($page, $perPage);

        [$whereClauses, $params] = $this->buildUserFilterClauses($filters);
        $whereClauses[]  = 'u.branch_id = :branch_id';
        $params[':branch_id'] = $branchId;

        $where = 'WHERE ' . implode(' AND ', $whereClauses);

        $total = $this->count(
            "SELECT COUNT(*) AS total FROM users u {$where}",
            $params,
        );

        $rows = $this->fetchAll(
            <<<SQL
            SELECT u.*, r.name AS role_name, r.slug AS role_slug
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            {$where}
            ORDER BY u.name ASC
            LIMIT :limit OFFSET :offset
            SQL,
            array_merge($params, [':limit' => $limit, ':offset' => $offset]),
        );

        return new PaginatedResult(
            items:   array_map(static fn (array $row) => User::fromArray($row), $rows),
            total:   $total,
            page:    $page,
            perPage: $perPage,
        );
    }

    /**
     * Paginated list of all users (cross-branch) with optional filters.
     *
     * @param  array<string, mixed> $filters
     */
    public function getAllWithFilters(array $filters, int $page, int $perPage): PaginatedResult
    {
        ['limit' => $limit, 'offset' => $offset] = $this->limitOffset($page, $perPage);

        [$whereClauses, $params] = $this->buildUserFilterClauses($filters);
        $where = $whereClauses !== []
            ? 'WHERE ' . implode(' AND ', $whereClauses)
            : '';

        $total = $this->count(
            "SELECT COUNT(*) AS total FROM users u {$where}",
            $params,
        );

        $rows = $this->fetchAll(
            <<<SQL
            SELECT u.*, r.name AS role_name, r.slug AS role_slug,
                   b.name AS branch_name
            FROM users u
            LEFT JOIN roles    r ON r.id = u.role_id
            LEFT JOIN branches b ON b.id = u.branch_id
            {$where}
            ORDER BY u.name ASC
            LIMIT :limit OFFSET :offset
            SQL,
            array_merge($params, [':limit' => $limit, ':offset' => $offset]),
        );

        return new PaginatedResult(
            items:   array_map(static fn (array $row) => User::fromArray($row), $rows),
            total:   $total,
            page:    $page,
            perPage: $perPage,
        );
    }

    // -------------------------------------------------------------------------
    // Writes
    // -------------------------------------------------------------------------

    /**
     * Insert a new user row and return the generated ID.
     *
     * @param  array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $this->execute(
            <<<SQL
            INSERT INTO users
                (branch_id, role_id, name, email, phone, password, avatar, status,
                 oauth_provider, oauth_provider_id,
                 failed_login_attempts, created_at, updated_at)
            VALUES
                (:branch_id, :role_id, :name, :email, :phone, :password, :avatar, :status,
                 :oauth_provider, :oauth_provider_id,
                 0, NOW(), NOW())
            SQL,
            [
                ':branch_id'        => $data['branch_id'],
                ':role_id'          => $data['role_id'],
                ':name'             => $data['name'],
                ':email'            => $data['email'],
                ':phone'            => $data['phone'] ?? null,
                ':password'         => $data['password'],
                ':avatar'           => $data['avatar'] ?? null,
                ':status'           => $data['status'] ?? UserStatus::Active->value,
                ':oauth_provider'   => $data['oauth_provider'] ?? null,
                ':oauth_provider_id'=> $data['oauth_provider_id'] ?? null,
            ],
        );

        return $this->lastInsertId();
    }

    public function findByOAuthId(string $provider, string $providerId): ?User
    {
        $row = $this->fetchOne(
            'SELECT * FROM users WHERE oauth_provider = :provider AND oauth_provider_id = :id AND deleted_at IS NULL LIMIT 1',
            [':provider' => $provider, ':id' => $providerId],
        );

        return $row !== null ? User::fromArray($row) : null;
    }

    public function linkOAuth(int $userId, string $provider, string $providerId): void
    {
        $this->modify(
            'UPDATE users SET oauth_provider = :provider, oauth_provider_id = :id, updated_at = NOW() WHERE id = :user_id',
            [':provider' => $provider, ':id' => $providerId, ':user_id' => $userId],
        );
    }

    /**
     * Update an existing user row.
     *
     * @param  array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $setClauses = [];
        $params     = [':id' => $id];

        $allowed = ['branch_id', 'role_id', 'name', 'email', 'phone', 'avatar', 'status', 'password'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $setClauses[]       = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if ($setClauses === []) {
            return false;
        }

        $setClauses[] = 'updated_at = NOW()';
        $setSQL       = implode(', ', $setClauses);

        return $this->modify(
            "UPDATE users SET {$setSQL} WHERE id = :id AND deleted_at IS NULL",
            $params,
        ) > 0;
    }

    /**
     * Soft-delete a user by setting deleted_at.
     */
    public function delete(int $id): bool
    {
        return $this->modify(
            'UPDATE users SET deleted_at = NOW(), updated_at = NOW(), status = :status WHERE id = :id AND deleted_at IS NULL',
            [':id' => $id, ':status' => UserStatus::Inactive->value],
        ) > 0;
    }

    // -------------------------------------------------------------------------
    // Auth-specific mutations
    // -------------------------------------------------------------------------

    /**
     * Set the failed_login_attempts counter for a user.
     */
    public function updateLoginAttempts(int $userId, int $attempts): void
    {
        $this->modify(
            'UPDATE users SET failed_login_attempts = :attempts, updated_at = NOW() WHERE id = :id',
            [':attempts' => $attempts, ':id' => $userId],
        );
    }

    /**
     * Lock a user account until the given date-time.
     */
    public function lockUser(int $userId, DateTime $until): void
    {
        $this->modify(
            'UPDATE users SET status = :status, locked_until = :until, updated_at = NOW() WHERE id = :id',
            [
                ':status' => UserStatus::Locked->value,
                ':until'  => $until->format('Y-m-d H:i:s'),
                ':id'     => $userId,
            ],
        );
    }

    /**
     * Clear the lock from a user account and reset failed attempts.
     */
    public function unlockUser(int $userId): void
    {
        $this->modify(
            <<<SQL
            UPDATE users
            SET status = :status,
                locked_until = NULL,
                failed_login_attempts = 0,
                updated_at = NOW()
            WHERE id = :id
            SQL,
            [':status' => UserStatus::Active->value, ':id' => $userId],
        );
    }

    /**
     * Stamp the last_login_at column with the current timestamp.
     */
    public function updateLastLogin(int $userId): void
    {
        $this->modify(
            'UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id',
            [':id' => $userId],
        );
    }

    // -------------------------------------------------------------------------
    // Password history
    // -------------------------------------------------------------------------

    /**
     * Persist a bcrypt hash to the password history table.
     */
    public function savePasswordHistory(int $userId, string $hash): void
    {
        $this->execute(
            'INSERT INTO password_history (user_id, password_hash, created_at) VALUES (:user_id, :hash, NOW())',
            [':user_id' => $userId, ':hash' => $hash],
        );
    }

    /**
     * Return the N most recent password hashes for a user (newest first).
     *
     * @return string[]
     */
    public function getPasswordHistory(int $userId, int $limit = 5): array
    {
        $rows = $this->fetchAll(
            'SELECT password_hash FROM password_history WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit',
            [':user_id' => $userId, ':limit' => $limit],
        );

        return array_column($rows, 'password_hash');
    }

    // -------------------------------------------------------------------------
    // Login audit log
    // -------------------------------------------------------------------------

    /**
     * Append an entry to the login_history audit table.
     *
     * @param  string       $status  'success' | 'failed' | 'locked'
     * @param  string|null  $reason  Human-readable failure reason, e.g. 'invalid_password'.
     */
    public function logLoginAttempt(
        int $userId,
        string $ip,
        string $ua,
        string $status,
        ?string $reason,
    ): void {
        $this->execute(
            <<<SQL
            INSERT INTO login_history (user_id, ip_address, user_agent, status, failure_reason, created_at)
            VALUES (:user_id, :ip, :ua, :status, :reason, NOW())
            SQL,
            [
                ':user_id' => $userId,
                ':ip'      => $ip,
                ':ua'      => $ua,
                ':status'  => $status,
                ':reason'  => $reason,
            ],
        );
    }

    /**
     * Fetch paginated activity log entries for a specific user.
     */
    public function getActivityLog(int $userId, int $page, int $perPage = 20): PaginatedResult
    {
        ['limit' => $limit, 'offset' => $offset] = $this->limitOffset($page, $perPage);

        $params = [':user_id' => $userId];

        $total = $this->count(
            'SELECT COUNT(*) AS total FROM activity_log WHERE user_id = :user_id',
            $params,
        );

        $rows = $this->fetchAll(
            <<<SQL
            SELECT id, action, description, ip_address, created_at
            FROM activity_log
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
            SQL,
            array_merge($params, [':limit' => $limit, ':offset' => $offset]),
        );

        return new PaginatedResult(
            items:   $rows,
            total:   $total,
            page:    $page,
            perPage: $perPage,
        );
    }

    /**
     * Fetch paginated login history for a specific user.
     */
    public function getLoginHistory(int $userId, int $page, int $perPage = 20): PaginatedResult
    {
        ['limit' => $limit, 'offset' => $offset] = $this->limitOffset($page, $perPage);

        $params = [':user_id' => $userId];

        $total = $this->count(
            'SELECT COUNT(*) AS total FROM login_history WHERE user_id = :user_id',
            $params,
        );

        $rows = $this->fetchAll(
            <<<SQL
            SELECT id, ip_address, user_agent, status, failure_reason, created_at
            FROM login_history
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
            SQL,
            array_merge($params, [':limit' => $limit, ':offset' => $offset]),
        );

        return new PaginatedResult(
            items:   $rows,
            total:   $total,
            page:    $page,
            perPage: $perPage,
        );
    }

    // -------------------------------------------------------------------------
    // Password fetch (auth helper — never returned in entity)
    // -------------------------------------------------------------------------

    /**
     * Retrieve only the bcrypt password hash for a user.
     * This is intentionally separate from the User entity to avoid
     * accidentally exposing the hash in serialised payloads.
     */
    public function getPasswordHash(int $userId): ?string
    {
        $row = $this->fetchOne(
            'SELECT password FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $userId],
        );

        return $row !== null ? (string) $row['password'] : null;
    }

    // -------------------------------------------------------------------------
    // Password-reset tokens
    // -------------------------------------------------------------------------

    /**
     * Upsert a password-reset token for a user.
     */
    public function upsertPasswordResetToken(int $userId, string $tokenHash, DateTime $expiresAt): void
    {
        $this->execute(
            <<<SQL
            INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, created_at)
            VALUES (:user_id, :token_hash, :expires_at, NOW())
            ON DUPLICATE KEY UPDATE
                token_hash = VALUES(token_hash),
                expires_at = VALUES(expires_at),
                used_at    = NULL,
                created_at = NOW()
            SQL,
            [
                ':user_id'    => $userId,
                ':token_hash' => $tokenHash,
                ':expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            ],
        );
    }

    /**
     * Retrieve a valid (unused, non-expired) password-reset token row.
     *
     * @return array<string, mixed>|null
     */
    public function findValidPasswordResetToken(string $tokenHash): ?array
    {
        return $this->fetchOne(
            <<<SQL
            SELECT prt.*, u.id AS user_id, u.email
            FROM password_reset_tokens prt
            INNER JOIN users u ON u.id = prt.user_id
            WHERE prt.token_hash = :token_hash
              AND prt.used_at IS NULL
              AND prt.expires_at > NOW()
              AND u.deleted_at IS NULL
            LIMIT 1
            SQL,
            [':token_hash' => $tokenHash],
        );
    }

    /**
     * Mark a password-reset token as consumed.
     */
    public function markTokenUsed(string $tokenHash): void
    {
        $this->modify(
            'UPDATE password_reset_tokens SET used_at = NOW() WHERE token_hash = :token_hash',
            [':token_hash' => $tokenHash],
        );
    }

    // -------------------------------------------------------------------------
    // Session management helpers
    // -------------------------------------------------------------------------

    /**
     * Persist a new user session record.
     */
    public function createSession(
        int $userId,
        string $sessionToken,
        string $refreshToken,
        string $ip,
        string $ua,
        DateTime $expiresAt,
    ): void {
        $this->execute(
            <<<SQL
            INSERT INTO user_sessions
                (user_id, session_token, refresh_token, ip_address, user_agent, expires_at, created_at)
            VALUES
                (:user_id, :session_token, :refresh_token, :ip, :ua, :expires_at, NOW())
            SQL,
            [
                ':user_id'       => $userId,
                ':session_token' => $sessionToken,
                ':refresh_token' => $refreshToken,
                ':ip'            => $ip,
                ':ua'            => $ua,
                ':expires_at'    => $expiresAt->format('Y-m-d H:i:s'),
            ],
        );
    }

    /**
     * Retrieve a non-expired session row by refresh token.
     *
     * @return array<string, mixed>|null
     */
    public function findSession(string $refreshToken): ?array
    {
        return $this->fetchOne(
            <<<SQL
            SELECT * FROM user_sessions
            WHERE refresh_token = :refresh_token
              AND expires_at > NOW()
              AND revoked_at IS NULL
            LIMIT 1
            SQL,
            [':refresh_token' => $refreshToken],
        );
    }

    /**
     * Revoke all active sessions for a user (e.g. on logout or password change).
     */
    public function revokeAllSessions(int $userId): void
    {
        $this->modify(
            'UPDATE user_sessions SET revoked_at = NOW() WHERE user_id = :user_id AND revoked_at IS NULL',
            [':user_id' => $userId],
        );
    }

    /**
     * Revoke a single session by its refresh token.
     */
    public function revokeSession(string $refreshToken): void
    {
        $this->modify(
            'UPDATE user_sessions SET revoked_at = NOW() WHERE refresh_token = :refresh_token',
            [':refresh_token' => $refreshToken],
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build reusable WHERE clauses and bindings for user list queries.
     *
     * @param  array<string, mixed>       $filters
     * @return array{0: string[], 1: array<string, mixed>}
     */
    private function buildUserFilterClauses(array $filters): array
    {
        $clauses = ['u.deleted_at IS NULL'];
        $params  = [];

        if (!empty($filters['search'])) {
            $clauses[]        = '(u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status'])) {
            $clauses[]        = 'u.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['role_id'])) {
            $clauses[]          = 'u.role_id = :role_id';
            $params[':role_id'] = (int) $filters['role_id'];
        }

        return [$clauses, $params];
    }
}
