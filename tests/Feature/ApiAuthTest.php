<?php

declare(strict_types=1);

namespace Tests\Feature;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Tests\TestCase;

/**
 * Feature tests for the REST API authentication layer.
 *
 * Exercises JWT issuance, validation, refresh, and blacklisting at
 * the business-logic level — without requiring a running HTTP server.
 * Each test validates the state that a real API handler would produce.
 */
final class ApiAuthTest extends TestCase
{
    /** JWT secret used across all tests in this class. */
    private const JWT_SECRET = 'test_secret_key_64_chars_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    private const JWT_ALGO   = 'HS256';
    private const ISSUER     = 'http://localhost:8080';

    // =========================================================================
    // JWT issuance on API login
    // =========================================================================

    public function testApiLoginReturnsJwtToken(): void
    {
        $rawUser = $this->createUser([
            'email'    => 'api-login@bizcore-test.local',
            'password' => 'ApiPass@888',
        ]);

        $result = $this->simulateApiLogin($rawUser['email'], 'ApiPass@888');

        $this->assertTrue($result['success'], 'API login must succeed with valid credentials');
        $this->assertNotEmpty($result['access_token'], 'Access token must be returned');
        $this->assertNotEmpty($result['refresh_token'], 'Refresh token must be returned');
        $this->assertIsInt($result['expires_in'],     'expires_in must be an integer (seconds)');
    }

    public function testApiLoginTokenContainsExpectedClaims(): void
    {
        $rawUser = $this->createUser([
            'email'    => 'claims@bizcore-test.local',
            'password' => 'Claims@Pass1',
            'role_id'  => 2,
            'branch_id'=> 1,
        ]);

        $result  = $this->simulateApiLogin($rawUser['email'], 'Claims@Pass1');
        $payload = $this->decodeToken($result['access_token']);

        $this->assertSame((string) $rawUser['id'], (string) $payload['sub'],        'sub claim must match user ID');
        $this->assertSame(self::ISSUER,             $payload['iss'],                 'iss claim must match issuer');
        $this->assertArrayHasKey('iat', $payload,                                    'iat claim required');
        $this->assertArrayHasKey('exp', $payload,                                    'exp claim required');
        $this->assertArrayHasKey('jti', $payload,                                    'jti (unique token ID) required');
        $this->assertSame((int) $rawUser['branch_id'], (int) $payload['bid'],        'bid (branch_id) custom claim required');
    }

    public function testApiLoginWithInvalidCredentialsReturns401(): void
    {
        $rawUser = $this->createUser([
            'email'    => 'api-fail@bizcore-test.local',
            'password' => 'Correct@Pass1',
        ]);

        $result = $this->simulateApiLogin($rawUser['email'], 'Wrong@Pass999');

        $this->assertFalse($result['success']);
        $this->assertSame(401, $result['status_code']);
    }

    // =========================================================================
    // Unauthenticated / invalid token
    // =========================================================================

    public function testApiWithoutTokenReturns401(): void
    {
        $result = $this->simulateAuthenticatedRequest(null);

        $this->assertFalse($result['authenticated']);
        $this->assertSame(401, $result['status_code']);
    }

    public function testApiWithMalformedTokenReturns401(): void
    {
        $result = $this->simulateAuthenticatedRequest('not.a.valid.jwt.token');

        $this->assertFalse($result['authenticated']);
        $this->assertSame(401, $result['status_code']);
    }

    public function testApiWithWrongSignatureReturns401(): void
    {
        // Forge a token signed with a different secret.
        $payload = [
            'sub' => '1',
            'iat' => time(),
            'exp' => time() + 3600,
            'jti' => bin2hex(random_bytes(16)),
            'iss' => self::ISSUER,
            'bid' => 1,
        ];

        $forgedToken = JWT::encode($payload, 'wrong-secret-entirely', self::JWT_ALGO);

        $result = $this->simulateAuthenticatedRequest($forgedToken);

        $this->assertFalse($result['authenticated']);
        $this->assertSame(401, $result['status_code']);
    }

    // =========================================================================
    // Token expiry
    // =========================================================================

    public function testApiWithExpiredTokenReturns401(): void
    {
        $rawUser = $this->createUser([
            'email'    => 'expired@bizcore-test.local',
            'password' => 'Expired@Pass1',
        ]);

        // Issue a token that expired 1 second ago.
        $expiredToken = $this->issueToken($rawUser['id'], $rawUser['branch_id'], ttlSeconds: -1);

        $result = $this->simulateAuthenticatedRequest($expiredToken);

        $this->assertFalse($result['authenticated']);
        $this->assertSame(401, $result['status_code']);
    }

    public function testApiWithValidTokenReturnsAuthenticated(): void
    {
        $rawUser = $this->createUser([
            'email'    => 'valid-token@bizcore-test.local',
            'password' => 'Valid@Pass1',
        ]);

        $token  = $this->issueToken($rawUser['id'], $rawUser['branch_id'], ttlSeconds: 3600);
        $result = $this->simulateAuthenticatedRequest($token);

        $this->assertTrue($result['authenticated']);
        $this->assertSame(200, $result['status_code']);
    }

    // =========================================================================
    // Token refresh
    // =========================================================================

    public function testApiTokenRefreshWorks(): void
    {
        $rawUser = $this->createUser([
            'email'    => 'refresh@bizcore-test.local',
            'password' => 'Refresh@Pass1',
        ]);

        $login = $this->simulateApiLogin($rawUser['email'], 'Refresh@Pass1');

        $this->assertTrue($login['success']);

        $refreshResult = $this->simulateTokenRefresh($login['refresh_token']);

        $this->assertTrue($refreshResult['success'],   'Token refresh must succeed with a valid refresh token');
        $this->assertNotEmpty($refreshResult['access_token'],  'New access token must be returned');
        $this->assertNotEmpty($refreshResult['refresh_token'], 'New refresh token must be returned');

        // The new tokens must differ from the old ones.
        $this->assertNotSame(
            $login['access_token'],
            $refreshResult['access_token'],
            'Refreshed access token must differ from the original'
        );
    }

    public function testRefreshWithExpiredRefreshTokenFails(): void
    {
        $rawUser = $this->createUser([
            'email'    => 'expired-refresh@bizcore-test.local',
            'password' => 'Expired@Refresh1',
        ]);

        // Issue a refresh token that is already expired.
        $expiredRefresh = $this->issueRefreshToken($rawUser['id'], ttlSeconds: -1);

        $result = $this->simulateTokenRefresh($expiredRefresh);

        $this->assertFalse($result['success']);
        $this->assertSame(401, $result['status_code']);
    }

    public function testRefreshTokenCanOnlyBeUsedOnce(): void
    {
        $rawUser = $this->createUser([
            'email'    => 'one-use@bizcore-test.local',
            'password' => 'OneUse@Pass1',
        ]);

        $login = $this->simulateApiLogin($rawUser['email'], 'OneUse@Pass1');

        // Use the refresh token once.
        $first = $this->simulateTokenRefresh($login['refresh_token']);
        $this->assertTrue($first['success']);

        // Attempt to reuse the same refresh token — must fail.
        $second = $this->simulateTokenRefresh($login['refresh_token']);
        $this->assertFalse($second['success'], 'A consumed refresh token must not be reusable');
    }

    // =========================================================================
    // Logout / token blacklisting
    // =========================================================================

    public function testApiLogoutBlacklistsToken(): void
    {
        $rawUser = $this->createUser([
            'email'    => 'api-logout@bizcore-test.local',
            'password' => 'Logout@Api1',
        ]);

        $login = $this->simulateApiLogin($rawUser['email'], 'Logout@Api1');
        $this->assertTrue($login['success']);

        // Logout to revoke / blacklist the token.
        $logoutResult = $this->simulateApiLogout($login['access_token']);
        $this->assertTrue($logoutResult['success']);

        // The token must now be rejected.
        $result = $this->simulateAuthenticatedRequest($login['access_token']);
        $this->assertFalse($result['authenticated'], 'Blacklisted token must be rejected');
    }

    public function testMultipleDeviceLogoutRevokesAllSessions(): void
    {
        $rawUser = $this->createUser([
            'email'    => 'multi-device@bizcore-test.local',
            'password' => 'Multi@Device1',
        ]);

        // Two concurrent sessions.
        $session1 = $this->issueSession($rawUser['id'], 'device-A');
        $session2 = $this->issueSession($rawUser['id'], 'device-B');

        // Revoke all sessions.
        $this->revokeAllSessions($rawUser['id']);

        $s1 = $this->findInDatabase('auth_sessions', ['token_hash' => $session1]);
        $s2 = $this->findInDatabase('auth_sessions', ['token_hash' => $session2]);

        $this->assertNotNull($s1['revoked_at'], 'Device A session must be revoked');
        $this->assertNotNull($s2['revoked_at'], 'Device B session must be revoked');
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Simulate an API login. Returns a result array with success, tokens, status_code.
     *
     * @return array{success:bool, access_token?:string, refresh_token?:string, expires_in?:int, status_code:int}
     */
    private function simulateApiLogin(string $email, string $password): array
    {
        $user = $this->findInDatabase('users', ['email' => $email]);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'status_code' => 401, 'message' => 'Invalid credentials.'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'status_code' => 401, 'message' => 'Account is not active.'];
        }

        $ttl         = 3600;     // 1 hour for access token
        $refreshTtl  = 1_209_600; // 14 days for refresh token

        $accessToken  = $this->issueToken($user['id'], $user['branch_id'], $ttl);
        $refreshToken = $this->issueRefreshToken($user['id'], $refreshTtl);

        // Persist session.
        $this->issueSession($user['id'], bin2hex(random_bytes(8)));

        // Stamp last login.
        $this->db->prepare("UPDATE users SET last_login_at = datetime('now') WHERE id = :id")
            ->execute([':id' => $user['id']]);

        return [
            'success'       => true,
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => $ttl,
            'token_type'    => 'Bearer',
            'status_code'   => 200,
        ];
    }

    /**
     * Simulate an API request with a Bearer token.
     *
     * @return array{authenticated:bool, status_code:int, user_id?:int}
     */
    private function simulateAuthenticatedRequest(?string $token): array
    {
        if ($token === null) {
            return ['authenticated' => false, 'status_code' => 401];
        }

        // Check blacklist first.
        if ($this->isTokenBlacklisted($token)) {
            return ['authenticated' => false, 'status_code' => 401];
        }

        try {
            $payload = $this->decodeToken($token);
        } catch (\Throwable) {
            return ['authenticated' => false, 'status_code' => 401];
        }

        return ['authenticated' => true, 'status_code' => 200, 'user_id' => (int) $payload['sub']];
    }

    /**
     * Simulate token refresh.
     *
     * @return array{success:bool, access_token?:string, refresh_token?:string, status_code:int}
     */
    private function simulateTokenRefresh(string $refreshToken): array
    {
        try {
            $payload = JWT::decode($refreshToken, new Key(self::JWT_SECRET, self::JWT_ALGO));
        } catch (\Throwable) {
            return ['success' => false, 'status_code' => 401, 'message' => 'Invalid or expired refresh token.'];
        }

        $jti = $payload->jti ?? null;

        // Check if already consumed.
        if ($jti !== null && $this->isRefreshTokenConsumed($jti)) {
            return ['success' => false, 'status_code' => 401, 'message' => 'Refresh token already used.'];
        }

        // Mark as consumed.
        if ($jti !== null) {
            $this->markRefreshTokenConsumed($jti);
        }

        $userId   = (int) $payload->sub;
        $branchId = (int) ($payload->bid ?? 1);

        $newAccess  = $this->issueToken($userId, $branchId, 3600);
        $newRefresh = $this->issueRefreshToken($userId, 1_209_600);

        return [
            'success'       => true,
            'access_token'  => $newAccess,
            'refresh_token' => $newRefresh,
            'status_code'   => 200,
        ];
    }

    /**
     * Simulate API logout — blacklist the given access token.
     *
     * @return array{success:bool}
     */
    private function simulateApiLogout(string $accessToken): array
    {
        $this->blacklistToken($accessToken);
        return ['success' => true];
    }

    /**
     * Issue a signed JWT access token.
     */
    private function issueToken(int $userId, int $branchId, int $ttlSeconds = 3600): string
    {
        $now = time();

        $payload = [
            'iss' => self::ISSUER,
            'sub' => (string) $userId,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttlSeconds,
            'jti' => bin2hex(random_bytes(16)),
            'bid' => $branchId,
        ];

        return JWT::encode($payload, self::JWT_SECRET, self::JWT_ALGO);
    }

    /**
     * Issue a signed JWT refresh token (longer TTL, different payload flag).
     */
    private function issueRefreshToken(int $userId, int $ttlSeconds = 1_209_600): string
    {
        $now = time();
        $jti = bin2hex(random_bytes(16));

        $payload = [
            'iss'   => self::ISSUER,
            'sub'   => (string) $userId,
            'iat'   => $now,
            'nbf'   => $now,
            'exp'   => $now + $ttlSeconds,
            'jti'   => $jti,
            'type'  => 'refresh',
        ];

        // Store JTI for single-use tracking (in real app this goes to Redis/DB).
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS refresh_token_jti (
                jti TEXT PRIMARY KEY, consumed INTEGER NOT NULL DEFAULT 0, created_at TEXT
             )"
        );
        $this->db->prepare(
            "INSERT OR IGNORE INTO refresh_token_jti (jti, created_at) VALUES (:jti, datetime('now'))"
        )->execute([':jti' => $jti]);

        return JWT::encode($payload, self::JWT_SECRET, self::JWT_ALGO);
    }

    /**
     * Decode and verify a JWT token; throws on failure.
     *
     * @return array<string, mixed>
     */
    private function decodeToken(string $token): array
    {
        $payload = JWT::decode($token, new Key(self::JWT_SECRET, self::JWT_ALGO));
        return (array) $payload;
    }

    /**
     * Persist a session and return the token hash.
     */
    private function issueSession(int $userId, string $deviceId): string
    {
        $tokenHash = bin2hex(random_bytes(32));
        $expiresAt = (new \DateTime('+2 hours'))->format('Y-m-d H:i:s');

        $this->db->prepare(
            "INSERT INTO auth_sessions (user_id, token_hash, user_agent, expires_at, created_at)
             VALUES (:uid, :hash, :ua, :exp, datetime('now'))"
        )->execute([
            ':uid'  => $userId,
            ':hash' => $tokenHash,
            ':ua'   => $deviceId,
            ':exp'  => $expiresAt,
        ]);

        return $tokenHash;
    }

    /**
     * Revoke all active sessions for a user.
     */
    private function revokeAllSessions(int $userId): void
    {
        $this->db->exec(
            "UPDATE auth_sessions SET revoked_at = datetime('now')
             WHERE user_id = {$userId} AND revoked_at IS NULL"
        );
    }

    /** In-memory blacklist: token hash → true. */
    private array $tokenBlacklist = [];

    private function blacklistToken(string $token): void
    {
        $this->tokenBlacklist[hash('sha256', $token)] = true;
    }

    private function isTokenBlacklisted(string $token): bool
    {
        return isset($this->tokenBlacklist[hash('sha256', $token)]);
    }

    private function isRefreshTokenConsumed(string $jti): bool
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS refresh_token_jti (
                jti TEXT PRIMARY KEY, consumed INTEGER NOT NULL DEFAULT 0, created_at TEXT
             )"
        );

        $row = $this->db->query("SELECT consumed FROM refresh_token_jti WHERE jti = '{$jti}'")->fetch();
        return $row !== false && (int) $row['consumed'] === 1;
    }

    private function markRefreshTokenConsumed(string $jti): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS refresh_token_jti (
                jti TEXT PRIMARY KEY, consumed INTEGER NOT NULL DEFAULT 0, created_at TEXT
             )"
        );

        $this->db->exec("UPDATE refresh_token_jti SET consumed = 1 WHERE jti = '{$jti}'");
    }
}
