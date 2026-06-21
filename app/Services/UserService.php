<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function __construct(private readonly AuthService $authService) {}

    public function create(array $data): User
    {
        if (User::where('email', $data['email'])->exists()) {
            throw new \InvalidArgumentException('This email address is already in use.');
        }

        $errors = $this->authService->validatePasswordStrength($data['password']);
        if ($errors !== []) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], [])->errors()->add('password', implode(' ', $errors))
            );
        }

        $hash = Hash::make($data['password']);

        $user = User::create([
            'branch_id' => $data['branch_id'],
            'role_id'   => $data['role_id'],
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'password'  => $hash,
            'status'    => $data['status'] ?? UserStatus::Inactive,
        ]);

        $this->savePasswordHistory($user->id, $hash);

        Log::info('User created.', ['user_id' => $user->id, 'email' => $user->email]);

        return $user;
    }

    public function update(int $id, array $data): User
    {
        $user = $this->findOrFail($id);

        if (!empty($data['email'])) {
            $conflict = User::where('email', $data['email'])->where('id', '!=', $id)->exists();
            if ($conflict) {
                throw new \InvalidArgumentException('This email address is already in use.');
            }
        }

        unset($data['password'], $data['status'], $data['deleted_at']);

        $user->update($data);
        Cache::forget("auth_user_{$id}");

        Log::info('User updated.', ['user_id' => $id]);

        return $user->fresh();
    }

    public function delete(int $id): void
    {
        $user = $this->findOrFail($id);
        $user->delete();
        Cache::forget("auth_user_{$id}");

        Log::info('User deleted.', ['user_id' => $id]);
    }

    public function updateStatus(int $id, UserStatus $status): void
    {
        $user = $this->findOrFail($id);
        $user->update(['status' => $status]);
        Cache::forget("auth_user_{$id}");

        Log::info('User status updated.', ['user_id' => $id, 'status' => $status->value]);
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = User::with(['role', 'branch'])->orderBy('name');

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(fn($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        return $query->paginate($perPage);
    }

    private function savePasswordHistory(int $userId, string $hash): void
    {
        \DB::table('password_reset_tokens')
            ->where('email', User::find($userId)?->email)
            ->delete();

        \DB::table('password_histories')->insert([
            'user_id'    => $userId,
            'password'   => $hash,
            'created_at' => now(),
        ]);

        \DB::table('password_histories')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->skip(5)
            ->take(PHP_INT_MAX)
            ->delete();
    }

    private function findOrFail(int $id): User
    {
        return User::findOrFail($id);
    }
}
