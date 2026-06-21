<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseApiController;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthApiController extends BaseApiController
{
    public function __construct(private readonly AuthService $authService) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->apiLogin($data['email'], $data['password'], $request->ip());

        if (!$result['success']) {
            return $this->error($result['message'], 401);
        }

        return $this->success($result);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->apiLogout($request->bearerToken());
        return $this->success(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($this->currentUser());
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $this->authService->sendPasswordResetLink($request->email);
        return $this->success(['message' => 'Password reset link sent if the email exists.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'                 => ['required'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'confirmed', 'min:8'],
        ]);

        $result = $this->authService->resetPassword($data);

        if (!$result) {
            return $this->error('Invalid or expired token.', 422);
        }

        return $this->success(['message' => 'Password reset successfully.']);
    }
}
