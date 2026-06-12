<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\Api\BaseApiController;
use App\Core\Auth;
use App\Http\Request;
use App\Services\AuthService;

class AuthApiController extends BaseApiController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly Auth $auth
    ) {}

    public function login(Request $request): void
    {
        $email    = $request->input('email', '');
        $password = $request->input('password', '');

        if (empty($email) || empty($password)) {
            $this->error('Email and password are required.', 422);
        }

        try {
            $result = $this->authService->login($email, $password, false);
            $this->success([
                'token'         => $result['token'],
                'refresh_token' => $result['refreshToken'],
                'expires_in'    => config('jwt.ttl', 60) * 60,
                'user'          => [
                    'id'        => $result['user']->id,
                    'name'      => $result['user']->name,
                    'email'     => $result['user']->email,
                    'role_id'   => $result['user']->roleId,
                    'branch_id' => $result['user']->branchId,
                ],
            ], 'Login successful.');
        } catch (\App\Exceptions\AccountLockedException $e) {
            $this->error('Account is locked. Please try again later.', 423);
        } catch (\App\Exceptions\InvalidCredentialsException $e) {
            $this->error('Invalid email or password.', 401);
        } catch (\Throwable $e) {
            $this->error('Authentication failed.', 500);
        }
    }

    public function logout(Request $request): void
    {
        $authHeader = $request->header('Authorization') ?? '';
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            $this->auth->blacklistToken($token);
        }
        $this->success(null, 'Logged out successfully.');
    }

    public function refresh(Request $request): void
    {
        $refreshToken = $request->input('refresh_token', '');
        if (empty($refreshToken)) {
            $this->error('Refresh token is required.', 422);
        }

        try {
            $result = $this->authService->refreshToken($refreshToken);
            $this->success([
                'token'         => $result['token'],
                'refresh_token' => $result['refreshToken'],
                'expires_in'    => config('jwt.ttl', 60) * 60,
            ]);
        } catch (\Throwable $e) {
            $this->error('Invalid or expired refresh token.', 401);
        }
    }

    public function me(Request $request): void
    {
        $user = $this->currentUser($request);
        if ($user === null) {
            $this->error('Unauthenticated.', 401);
            return;
        }
        $this->success([
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'phone'     => $user->phone,
            'avatar'    => $user->avatar,
            'status'    => $user->status->value,
            'role_id'   => $user->roleId,
            'branch_id' => $user->branchId,
        ]);
    }
}
