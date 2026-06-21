<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseApiController;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserApiController extends BaseApiController
{
    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        return $this->paginate($this->userService->paginate($request->all()));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:150'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'password'  => ['required', 'min:8'],
            'role_id'   => ['required', 'integer', 'exists:roles,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        return $this->created($this->userService->create($data));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(\App\Models\User::with(['role', 'branch'])->findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:150'],
            'email'     => ['sometimes', 'email', 'unique:users,email,' . $id],
            'role_id'   => ['sometimes', 'integer', 'exists:roles,id'],
            'branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
        ]);

        return $this->success($this->userService->update($id, $data));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->userService->delete($id);
        return $this->success(['message' => 'User deleted.']);
    }
}
