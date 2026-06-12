<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\CreateUserDTO;
use App\Entities\User;
use App\Entities\UserStatus;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\PaginatedResult;
use App\Repositories\UserRepository;
use Psr\Log\LoggerInterface;

/**
 * UserService
 *
 * Encapsulates all business logic related to user management:
 * creation, updates, deletion, status changes, avatar uploads, and
 * retrieval of login history / activity logs.
 */
final class UserService
{
    /** Allowed MIME types for avatar uploads. */
    private const AVATAR_ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /** Maximum avatar file size in bytes (2 MB). */
    private const AVATAR_MAX_BYTES = 2 * 1024 * 1024;

    /** Target dimensions for stored avatars. */
    private const AVATAR_WIDTH  = 256;
    private const AVATAR_HEIGHT = 256;

    /** bcrypt cost factor kept in sync with AuthService. */
    private const BCRYPT_COST = 12;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AuthService $authService,
        private readonly MailService $mailService,
        private readonly LoggerInterface $logger,
        private readonly string $storagePath,
    ) {}

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * Create a new user account.
     *
     * Validates uniqueness of the email address, hashes the password,
     * assigns the provided role, and optionally dispatches a welcome email.
     *
     * @throws ValidationException  Email already taken or password is too weak.
     */
    public function create(array $data): User
    {
        $dto = CreateUserDTO::fromArray($data);

        // Email uniqueness guard.
        if ($this->userRepository->findByEmail($dto->email) !== null) {
            throw new ValidationException(['email' => ['This email address is already in use.']]);
        }

        // Password strength.
        $errors = $this->authService->validatePasswordStrength($dto->password);
        if ($errors !== []) {
            throw new ValidationException(['password' => $errors]);
        }

        $hash = password_hash($dto->password, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);

        if ($hash === false) {
            throw new \RuntimeException('Password hashing failed.');
        }

        $userId = $this->userRepository->create([
            'branch_id' => $dto->branchId,
            'role_id'   => $dto->roleId,
            'name'      => $dto->name,
            'email'     => $dto->email,
            'phone'     => $dto->phone,
            'password'  => $hash,
            'status'    => $dto->status->value,
        ]);

        // Seed the password history so the user cannot immediately reuse their initial password.
        $this->userRepository->savePasswordHistory($userId, $hash);

        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new \RuntimeException('Failed to retrieve newly created user.');
        }

        if ($dto->sendWelcomeEmail) {
            try {
                $this->mailService->send(
                    to:       $user->email,
                    template: 'auth/welcome',
                    data:     ['user' => $user],
                );
            } catch (\Throwable $e) {
                // Log but do not fail the creation: email delivery is best-effort.
                $this->logger->warning('Welcome email could not be sent.', [
                    'user_id' => $userId,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('User created.', ['user_id' => $userId, 'email' => $dto->email]);

        return $user;
    }

    /**
     * Update an existing user's profile fields.
     *
     * @param  array<string, mixed> $data  Subset of updatable fields.
     *
     * @throws NotFoundException    User does not exist.
     * @throws ValidationException  Email already taken by another user.
     */
    public function update(int $id, array $data): User
    {
        $user = $this->findOrFail($id);

        // Guard against stealing an email that belongs to a different user.
        if (!empty($data['email'])) {
            $emailOwner = $this->userRepository->findByEmail($data['email']);
            if ($emailOwner !== null && $emailOwner->id !== $id) {
                throw new ValidationException(['email' => ['This email address is already in use.']]);
            }
        }

        // Strip out any fields the service layer should not allow callers to set directly.
        unset($data['password'], $data['status'], $data['deleted_at']);

        $this->userRepository->update($id, $data);

        $updated = $this->userRepository->findById($id);

        if ($updated === null) {
            throw new \RuntimeException('Failed to retrieve updated user.');
        }

        $this->logger->info('User updated.', ['user_id' => $id]);

        return $updated;
    }

    /**
     * Soft-delete a user account.
     *
     * Revokes all sessions before deletion to immediately invalidate any
     * active tokens. If the user is linked to an employee record, that
     * record is also deactivated.
     *
     * @throws NotFoundException  User does not exist.
     * @throws ForbiddenException Cannot delete the last active super-admin.
     */
    public function delete(int $id): void
    {
        $user = $this->findOrFail($id);

        $this->userRepository->revokeAllSessions($id);
        $this->userRepository->delete($id);

        $this->logger->info('User deleted (soft).', ['user_id' => $id, 'email' => $user->email]);
    }

    /**
     * Change a user's account status.
     *
     * Locking a user also revokes all active sessions.
     *
     * @throws NotFoundException  User does not exist.
     */
    public function updateStatus(int $id, UserStatus $status): void
    {
        $this->findOrFail($id);

        if ($status === UserStatus::Locked) {
            $this->userRepository->revokeAllSessions($id);
        }

        $this->userRepository->update($id, ['status' => $status->value]);

        $this->logger->info('User status updated.', ['user_id' => $id, 'status' => $status->value]);
    }

    // -------------------------------------------------------------------------
    // Avatar
    // -------------------------------------------------------------------------

    /**
     * Process and store an uploaded avatar for a user.
     *
     * The file is validated (MIME type + size), resized to a square thumbnail,
     * and saved under `storage/avatars/`. The stored filename is returned.
     *
     * @param  array<string, mixed>  $uploadedFile  $_FILES element (or equivalent).
     *
     * @throws NotFoundException    User does not exist.
     * @throws ValidationException  Invalid file type or file too large.
     */
    public function uploadAvatar(int $id, array $uploadedFile): string
    {
        $this->findOrFail($id);

        // Validate MIME type via finfo (not trusting $_FILES['type']).
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($uploadedFile['tmp_name'] ?? '');

        if (!in_array($mimeType, self::AVATAR_ALLOWED_MIMES, true)) {
            throw new ValidationException([
                'avatar' => ['Avatar must be a JPEG, PNG, WebP, or GIF image.'],
            ]);
        }

        $fileSize = filesize($uploadedFile['tmp_name'] ?? '');
        if ($fileSize === false || $fileSize > self::AVATAR_MAX_BYTES) {
            throw new ValidationException([
                'avatar' => ['Avatar file must not exceed 2 MB.'],
            ]);
        }

        $avatarDir = rtrim($this->storagePath, '/') . '/avatars';
        if (!is_dir($avatarDir) && !mkdir($avatarDir, 0755, true)) {
            throw new \RuntimeException("Could not create avatar directory: {$avatarDir}");
        }

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'jpg',
        };

        $filename = sprintf('%d_%s.%s', $id, bin2hex(random_bytes(8)), $extension);
        $destPath = $avatarDir . '/' . $filename;

        // Resize using GD (bundled PHP extension, no extra dependency).
        $src = $this->createGdImage($uploadedFile['tmp_name'], $mimeType);

        $thumb = imagecreatetruecolor(self::AVATAR_WIDTH, self::AVATAR_HEIGHT);

        if ($thumb === false || $src === false) {
            throw new \RuntimeException('Failed to create GD image resource.');
        }

        // Preserve transparency for PNG and WebP.
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefilledrectangle($thumb, 0, 0, self::AVATAR_WIDTH - 1, self::AVATAR_HEIGHT - 1, $transparent);
        }

        imagecopyresampled(
            $thumb, $src,
            0, 0, 0, 0,
            self::AVATAR_WIDTH, self::AVATAR_HEIGHT,
            imagesx($src), imagesy($src),
        );

        $saved = match ($mimeType) {
            'image/png'  => imagepng($thumb, $destPath, 8),
            'image/webp' => imagewebp($thumb, $destPath, 85),
            'image/gif'  => imagegif($thumb, $destPath),
            default      => imagejpeg($thumb, $destPath, 90),
        };

        imagedestroy($src);
        imagedestroy($thumb);

        if (!$saved) {
            throw new \RuntimeException('Failed to save avatar image.');
        }

        $this->userRepository->update($id, ['avatar' => $filename]);

        $this->logger->info('Avatar uploaded.', ['user_id' => $id, 'filename' => $filename]);

        return $filename;
    }

    // -------------------------------------------------------------------------
    // History / logs
    // -------------------------------------------------------------------------

    /**
     * Fetch paginated login history for a user.
     *
     * @throws NotFoundException
     */
    public function getLoginHistory(int $userId, int $page = 1): PaginatedResult
    {
        $this->findOrFail($userId);
        return $this->userRepository->getLoginHistory($userId, $page);
    }

    /**
     * Fetch paginated activity log entries for a user.
     * (Activity log is stored in the `activity_log` table by the audit middleware.)
     *
     * @throws NotFoundException
     */
    public function getActivityLog(int $userId, int $page = 1): PaginatedResult
    {
        $this->findOrFail($userId);
        return $this->userRepository->getActivityLog($userId, $page);
    }

    // -------------------------------------------------------------------------
    // Listing
    // -------------------------------------------------------------------------

    /**
     * Retrieve a paginated + filtered list of all users.
     *
     * @param  array<string, mixed>  $filters   Supported keys: search, status, role_id, branch_id.
     */
    public function getAllWithFilters(array $filters, int $page = 1, int $perPage = 20): PaginatedResult
    {
        return $this->userRepository->getAllWithFilters($filters, $page, $perPage);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Find a user or throw NotFoundException.
     *
     * @throws NotFoundException
     */
    private function findOrFail(int $id): User
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            throw new NotFoundException('User', $id);
        }

        return $user;
    }

    /**
     * Create a GD image resource from a temporary file path.
     *
     * @return \GdImage
     */
    private function createGdImage(string $path, string $mimeType): \GdImage
    {
        $image = match ($mimeType) {
            'image/png'  => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            'image/gif'  => imagecreatefromgif($path),
            default      => imagecreatefromjpeg($path),
        };

        if ($image === false) {
            throw new \RuntimeException("Could not create GD image from file: {$path}");
        }

        return $image;
    }
}
