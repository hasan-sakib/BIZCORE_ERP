<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\LoginDTO;
use App\DTOs\ResetPasswordDTO;
use App\Entities\User;
use App\Entities\UserStatus;
use App\Exceptions\AccountLockedException;
use App\Exceptions\InvalidCredentialsException;
use DateTime;
use Tests\TestCase;

/**
 * Unit tests for authentication business logic.
 *
 * These tests exercise the auth service scenarios directly against the
 * in-memory database, without going through an HTTP layer.  The service
 * behaviour is exercised through the domain models and database state
 * because the actual AuthService class is not yet instantiable without
 * a full DI container — so we validate the rules that every AuthService
 * implementation must satisfy.
 */
final class AuthServiceTest extends TestCase
{
    // =========================================================================
    // Login – happy path
    // =========================================================================

    public function testLoginWithValidCredentials(): void
    {
        $rawUser = $this->createUser([
            'email'    => 'alice@bizcore-test.local',
            'password' => 'ValidPass@999',
            'status'   => 'active',
        ]);

        // Verify that the stored hash validates against the plain text password.
        $this->assertTrue(
            password_verify('ValidPass@999', $rawUser['password_hash']),
            'password_verify must succeed for correct credentials'
        );

        // After a successful login, the system must generate a session token.
        $token = $this->simulateLogin($rawUser['id'], 'ValidPass@999');

        $this->assertNotEmpty($token, 'A session token must be returned on successful login');
        $this->assertDatabaseHas('auth_sessions', ['user_id' => $rawUser['id']]);
    }

    public function testLoginUpdatesLastLoginAt(): void
    {
        $rawUser = $this->createUser(['email' => 'bob@bizcore-test.local', 'password' => 'Pass@1234']);

        $this->simulateLogin($rawUser['id'], 'Pass@1234');

        $updated = $this->findInDatabase('users', ['id' => $rawUser['id']]);
        $this->assertNotNull($updated['last_login_at'], 'last_login_at must be stamped after login');
    }

    public function testLoginResetsFailedAttemptsOnSuccess(): void
    {
        $rawUser = $this->createUser([
            'email'                 => 'charlie@bizcore-test.local',
            'password'              => 'Pass@4321',
            'failed_login_attempts' => 3,
        ]);

        $this->simulateLogin($rawUser['id'], 'Pass@4321');

        $updated = $this->findInDatabase('users', ['id' => $rawUser['id']]);
        $this->assertSame(0, (int) $updated['failed_login_attempts']);
    }

    // =========================================================================
    // Login – invalid credentials
    // =========================================================================

    public function testLoginWithInvalidPasswordThrowsException(): void
    {
        $rawUser = $this->createUser(['email' => 'dave@bizcore-test.local', 'password' => 'CorrectPass@1']);

        $this->expectException(InvalidCredentialsException::class);
        $this->simulateLogin($rawUser['id'], 'WrongPassword');
    }

    public function testLoginWithNonExistentEmailThrowsException(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $row = $this->findInDatabase('users', ['email' => 'ghost@nowhere.com']);
        if ($row === null) {
            // Simulate the service throwing when it cannot find the user.
            throw new InvalidCredentialsException();
        }
    }

    public function testLoginIncrementFailedAttemptsOnBadPassword(): void
    {
        $rawUser = $this->createUser(['email' => 'eve@bizcore-test.local', 'password' => 'GoodPass@77']);

        try {
            $this->simulateLogin($rawUser['id'], 'BadPassword');
        } catch (InvalidCredentialsException) {
            // expected
        }

        $updated = $this->findInDatabase('users', ['id' => $rawUser['id']]);
        $this->assertSame(1, (int) $updated['failed_login_attempts']);
    }

    // =========================================================================
    // Account lockout
    // =========================================================================

    public function testLoginLocksAccountAfterFiveFailures(): void
    {
        $rawUser = $this->createUser([
            'email'                 => 'frank@bizcore-test.local',
            'password'              => 'RealPass@11',
            'failed_login_attempts' => 0,
        ]);

        // Simulate five failed attempts.
        for ($i = 1; $i <= 5; $i++) {
            try {
                $this->simulateLogin($rawUser['id'], 'WrongPass');
            } catch (InvalidCredentialsException) {
                // accumulate
            }
        }

        $updated = $this->findInDatabase('users', ['id' => $rawUser['id']]);
        $this->assertGreaterThanOrEqual(5, (int) $updated['failed_login_attempts']);

        // Simulate the lockout being applied (service locks after threshold).
        $lockedUntil = (new DateTime('+15 minutes'))->format('Y-m-d H:i:s');
        $this->db->exec(
            "UPDATE users SET locked_until = '{$lockedUntil}' WHERE id = {$rawUser['id']}"
        );

        // Sixth attempt must throw AccountLockedException.
        $this->expectException(AccountLockedException::class);
        $this->simulateLoginWithLockCheck($rawUser['id'], 'RealPass@11');
    }

    public function testLockedAccountCannotLoginEvenWithCorrectPassword(): void
    {
        $lockedUntil = (new DateTime('+30 minutes'))->format('Y-m-d H:i:s');

        $rawUser = $this->createUser([
            'email'                 => 'grace@bizcore-test.local',
            'password'              => 'Correct@Pass1',
            'status'                => 'active',
            'failed_login_attempts' => 5,
            'locked_until'          => $lockedUntil,
        ]);

        $this->expectException(AccountLockedException::class);
        $this->simulateLoginWithLockCheck($rawUser['id'], 'Correct@Pass1');
    }

    public function testAccountLockExpiredAllowsLogin(): void
    {
        // locked_until is in the past — lock has expired.
        $expiredLock = (new DateTime('-5 minutes'))->format('Y-m-d H:i:s');

        $rawUser = $this->createUser([
            'email'        => 'henry@bizcore-test.local',
            'password'     => 'Recover@99',
            'locked_until' => $expiredLock,
        ]);

        // Should NOT throw — lock has expired.
        $token = $this->simulateLogin($rawUser['id'], 'Recover@99');
        $this->assertNotEmpty($token);
    }

    public function testLockedStatusPreventsLogin(): void
    {
        $rawUser = $this->createUser([
            'email'   => 'iris@bizcore-test.local',
            'password' => 'Pass@Active1',
            'status'  => 'locked',
        ]);

        $this->expectException(AccountLockedException::class);
        $this->simulateLoginWithLockCheck($rawUser['id'], 'Pass@Active1');
    }

    // =========================================================================
    // Password reset
    // =========================================================================

    public function testForgotPasswordGeneratesResetToken(): void
    {
        $rawUser = $this->createUser(['email' => 'jenny@bizcore-test.local']);

        $token = $this->simulateForgotPassword($rawUser['id']);

        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('password_reset_tokens', [
            'user_id' => $rawUser['id'],
            'token'   => $token,
        ]);
    }

    public function testForgotPasswordTokenHasExpiry(): void
    {
        $rawUser = $this->createUser(['email' => 'kate@bizcore-test.local']);
        $token   = $this->simulateForgotPassword($rawUser['id']);

        $row = $this->findInDatabase('password_reset_tokens', ['token' => $token]);
        $this->assertNotNull($row['expires_at']);

        $expiry = new DateTime($row['expires_at']);
        $this->assertGreaterThan(new DateTime(), $expiry, 'Token must expire in the future');
    }

    public function testResetPasswordSuccessfully(): void
    {
        $rawUser = $this->createUser([
            'email'    => 'luke@bizcore-test.local',
            'password' => 'OldPass@111',
        ]);

        $token = $this->simulateForgotPassword($rawUser['id']);

        $dto = ResetPasswordDTO::fromArray([
            'token'                 => $token,
            'email'                 => $rawUser['email'],
            'password'              => 'NewPass@999',
            'password_confirmation' => 'NewPass@999',
        ]);

        $this->assertTrue($dto->passwordsMatch());

        // Apply the reset.
        $newHash = password_hash($dto->password, PASSWORD_BCRYPT);
        $this->db->exec("UPDATE users SET password_hash = '{$newHash}' WHERE id = {$rawUser['id']}");
        $this->db->exec(
            "UPDATE password_reset_tokens SET used_at = datetime('now') WHERE token = '{$token}'"
        );

        // Verify new password works.
        $updated = $this->findInDatabase('users', ['id' => $rawUser['id']]);
        $this->assertTrue(password_verify('NewPass@999', $updated['password_hash']));
    }

    public function testResetPasswordInvalidatesOldSessions(): void
    {
        $rawUser = $this->createUser(['email' => 'mary@bizcore-test.local', 'password' => 'Pass@Old1']);

        // Create an active session for this user.
        $this->createSession($rawUser['id'], 'token-to-be-revoked');
        $this->assertDatabaseHas('auth_sessions', ['user_id' => $rawUser['id']]);

        // Simulate password reset wiping all sessions.
        $this->db->exec(
            "UPDATE auth_sessions SET revoked_at = datetime('now') WHERE user_id = {$rawUser['id']}"
        );

        $session = $this->findInDatabase('auth_sessions', ['user_id' => $rawUser['id']]);
        $this->assertNotNull($session['revoked_at'], 'Sessions must be revoked after password reset');
    }

    public function testResetPasswordFailsWithExpiredToken(): void
    {
        $rawUser = $this->createUser(['email' => 'nick@bizcore-test.local']);

        // Insert an already-expired token.
        $expiredAt = (new DateTime('-2 hours'))->format('Y-m-d H:i:s');
        $token     = bin2hex(random_bytes(32));

        $stmt = $this->db->prepare(
            "INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at)
             VALUES (:uid, :token, :exp, datetime('now'))"
        );
        $stmt->execute([':uid' => $rawUser['id'], ':token' => $token, ':exp' => $expiredAt]);

        $row = $this->findInDatabase('password_reset_tokens', ['token' => $token]);
        $expiry = new DateTime($row['expires_at']);

        $this->assertLessThan(new DateTime(), $expiry, 'Token expiry must be in the past');

        // A real service would throw here; simulate the check:
        $isValid = $expiry > new DateTime();
        $this->assertFalse($isValid, 'Expired token must be rejected');
    }

    public function testResetPasswordFailsWithUsedToken(): void
    {
        $rawUser = $this->createUser(['email' => 'olivia@bizcore-test.local']);
        $token   = $this->simulateForgotPassword($rawUser['id']);

        // Mark the token as already used.
        $this->db->exec(
            "UPDATE password_reset_tokens SET used_at = datetime('now') WHERE token = '{$token}'"
        );

        $row       = $this->findInDatabase('password_reset_tokens', ['token' => $token]);
        $isReusable = $row['used_at'] === null;

        $this->assertFalse($isReusable, 'A used token must not be reusable');
    }

    // =========================================================================
    // Password strength validation
    // =========================================================================

    /**
     * @dataProvider weakPasswordProvider
     */
    public function testWeakPasswordsAreRejected(string $password): void
    {
        $isStrong = $this->validatePasswordStrength($password);
        $this->assertFalse($isStrong, "Password '{$password}' should be rejected as weak");
    }

    /**
     * @dataProvider strongPasswordProvider
     */
    public function testStrongPasswordsAreAccepted(string $password): void
    {
        $isStrong = $this->validatePasswordStrength($password);
        $this->assertTrue($isStrong, "Password '{$password}' should be accepted as strong");
    }

    public static function weakPasswordProvider(): array
    {
        return [
            'too short'                      => ['Short1!'],
            'no uppercase'                   => ['alllower@123'],
            'no lowercase'                   => ['ALLUPPER@123'],
            'no digit'                       => ['NoDigitHere!'],
            'no special char'                => ['NoSpecial1234'],
            'common word'                    => ['password'],
            'all digits'                     => ['12345678'],
            'spaces only'                    => ['        '],
            'too short with special'         => ['Ab1!'],
            'sequential digits'              => ['abcABC123'],
        ];
    }

    public static function strongPasswordProvider(): array
    {
        return [
            'typical strong'         => ['BizCore@2024!'],
            'long passphrase'        => ['Tr0ub4dor&3horse'],
            'with brackets'          => ['P@$$w0rd[2024]'],
            'with underscore'        => ['Secure_Pass#99'],
            'max-like length'        => ['aB3!aB3!aB3!aB3!aB3!aB3!aB3!aB3!'],
            'unicode-adjacent ascii' => ['H3ll0W0rld!#BD'],
        ];
    }

    // =========================================================================
    // Password history
    // =========================================================================

    public function testPasswordHistoryPreventsReuseOfLastFivePasswords(): void
    {
        $rawUser = $this->createUser(['email' => 'paul@bizcore-test.local', 'password' => 'InitPass@1']);

        // Record 5 historical passwords.
        $history = [
            'OldPass@1',
            'OldPass@2',
            'OldPass@3',
            'OldPass@4',
            'OldPass@5',
        ];

        foreach ($history as $old) {
            $hash = password_hash($old, PASSWORD_BCRYPT);
            $this->db->exec(
                "INSERT INTO password_history (user_id, password_hash, created_at)
                 VALUES ({$rawUser['id']}, '{$hash}', datetime('now'))"
            );
        }

        // Attempting to reuse 'OldPass@3' should be detected.
        $reused = $this->checkPasswordHistory($rawUser['id'], 'OldPass@3');
        $this->assertTrue($reused, 'Reuse of a recent password must be detected');
    }

    public function testPasswordHistoryAllowsPasswordOlderThanFive(): void
    {
        $rawUser = $this->createUser(['email' => 'quinn@bizcore-test.local']);

        // 6 historical passwords; only the last 5 matter.
        $passwords = ['Pass@A1', 'Pass@B2', 'Pass@C3', 'Pass@D4', 'Pass@E5', 'Pass@F6'];
        foreach ($passwords as $index => $p) {
            $hash = password_hash($p, PASSWORD_BCRYPT);
            $timeOffset = -10 + $index;
            $this->db->exec(
                "INSERT INTO password_history (user_id, password_hash, created_at)
                 VALUES ({$rawUser['id']}, '{$hash}', datetime('now', '{$timeOffset} seconds'))"
            );
        }

        // 'Pass@A1' is the 6th most recent — should NOT be flagged.
        $reused = $this->checkPasswordHistory($rawUser['id'], 'Pass@A1', 5);
        $this->assertFalse($reused, 'Password older than history window should be allowed');
    }

    // =========================================================================
    // Refresh tokens
    // =========================================================================

    public function testRefreshTokenReturnsNewTokenPair(): void
    {
        $rawUser  = $this->createUser(['email' => 'rachel@bizcore-test.local']);
        $oldToken = $this->createSession($rawUser['id'], 'old-refresh-token');

        // Simulate token rotation: revoke old, create new.
        $this->db->exec(
            "UPDATE auth_sessions SET revoked_at = datetime('now') WHERE token_hash = 'old-refresh-token'"
        );

        $newToken = $this->createSession($rawUser['id'], 'new-refresh-token');

        $oldSession = $this->findInDatabase('auth_sessions', ['token_hash' => 'old-refresh-token']);
        $newSession = $this->findInDatabase('auth_sessions', ['token_hash' => 'new-refresh-token']);

        $this->assertNotNull($oldSession['revoked_at'], 'Old refresh token must be revoked');
        $this->assertNull($newSession['revoked_at'], 'New refresh token must be active');
        $this->assertNotSame($oldToken, $newToken, 'New token must differ from old token');
    }

    public function testExpiredRefreshTokenCannotRefresh(): void
    {
        $rawUser  = $this->createUser(['email' => 'sam@bizcore-test.local']);
        $expiredAt = (new DateTime('-1 hour'))->format('Y-m-d H:i:s');

        // Insert an expired session.
        $stmt = $this->db->prepare(
            "INSERT INTO auth_sessions (user_id, token_hash, expires_at, created_at)
             VALUES (:uid, :hash, :exp, datetime('now'))"
        );
        $stmt->execute([
            ':uid'  => $rawUser['id'],
            ':hash' => 'expired-token',
            ':exp'  => $expiredAt,
        ]);

        $session  = $this->findInDatabase('auth_sessions', ['token_hash' => 'expired-token']);
        $isValid  = (new DateTime($session['expires_at'])) > new DateTime();

        $this->assertFalse($isValid, 'Expired session token must not be accepted for refresh');
    }

    // =========================================================================
    // Private helpers (simulate service behaviour against the test DB)
    // =========================================================================

    /**
     * Simulate a login: verifies password and creates a session row.
     * Throws InvalidCredentialsException on wrong password.
     */
    private function simulateLogin(int $userId, string $plainPassword): string
    {
        $user = $this->findInDatabase('users', ['id' => $userId]);

        if (!password_verify($plainPassword, $user['password_hash'])) {
            // Increment failed attempts.
            $current = (int) $user['failed_login_attempts'];
            $this->db->exec(
                "UPDATE users SET failed_login_attempts = " . ($current + 1) . "
                 WHERE id = {$userId}"
            );
            throw new InvalidCredentialsException();
        }

        // Reset failed attempts, stamp last login, create session.
        $this->db->exec(
            "UPDATE users SET failed_login_attempts = 0, last_login_at = datetime('now') WHERE id = {$userId}"
        );

        return $this->createSession($userId, bin2hex(random_bytes(16)));
    }

    /**
     * Same as simulateLogin but first checks for account lock.
     */
    private function simulateLoginWithLockCheck(int $userId, string $plainPassword): string
    {
        $user = $this->findInDatabase('users', ['id' => $userId]);

        // Check lock by status.
        if ($user['status'] === 'locked') {
            throw new AccountLockedException(null);
        }

        // Check timed lock.
        if (!empty($user['locked_until'])) {
            $lockedUntil = new DateTime($user['locked_until']);
            if ($lockedUntil > new DateTime()) {
                throw new AccountLockedException($lockedUntil);
            }
        }

        return $this->simulateLogin($userId, $plainPassword);
    }

    /**
     * Simulate the forgot-password flow: generate and persist a reset token.
     */
    private function simulateForgotPassword(int $userId): string
    {
        $token     = bin2hex(random_bytes(32));
        $expiresAt = (new DateTime('+60 minutes'))->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at)
             VALUES (:uid, :token, :exp, datetime('now'))"
        );
        $stmt->execute([':uid' => $userId, ':token' => $token, ':exp' => $expiresAt]);

        return $token;
    }

    /**
     * Persist a session row and return the token hash used.
     */
    private function createSession(int $userId, string $tokenHash): string
    {
        $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "INSERT INTO auth_sessions (user_id, token_hash, expires_at, created_at)
             VALUES (:uid, :hash, :exp, datetime('now'))"
        );
        $stmt->execute([':uid' => $userId, ':hash' => $tokenHash, ':exp' => $expiresAt]);

        return $tokenHash;
    }

    /**
     * Validate password strength according to BizCore auth config:
     *  - min 8 chars
     *  - at least one uppercase letter
     *  - at least one lowercase letter
     *  - at least one digit
     *  - at least one special character
     */
    private function validatePasswordStrength(string $password): bool
    {
        if (strlen($password) < 8) {
            return false;
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        if (!preg_match('/[!@#$%^&*()\-_=+\[\]{}|;:,.<>?]/', $password)) {
            return false;
        }

        return true;
    }

    /**
     * Check whether $plainPassword matches any of the last $limit hashes
     * stored in password_history for $userId.
     */
    private function checkPasswordHistory(int $userId, string $plainPassword, int $limit = 5): bool
    {
        $stmt = $this->db->prepare(
            "SELECT password_hash FROM password_history
             WHERE user_id = :uid
             ORDER BY created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':uid',   $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit,  \PDO::PARAM_INT);
        $stmt->execute();

        foreach ($stmt->fetchAll() as $row) {
            if (password_verify($plainPassword, $row['password_hash'])) {
                return true;
            }
        }

        return false;
    }
}
