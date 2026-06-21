<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseApiController;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerApiController extends BaseApiController
{
    public function __construct(private readonly CustomerService $customerService) {}

    public function index(Request $request): JsonResponse
    {
        return $this->paginate($this->customerService->paginate($request->all()));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:150'],
            'email'         => ['nullable', 'email', 'unique:customers,email'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'credit_limit'  => ['nullable', 'numeric', 'min:0'],
        ]);
        return $this->created($this->customerService->create($data));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success($this->customerService->findWithHistory($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['sometimes', 'string', 'max:150'],
            'email'        => ['sometimes', 'email', 'unique:customers,email,' . $id],
            'phone'        => ['sometimes', 'string', 'max:30'],
            'credit_limit' => ['sometimes', 'numeric', 'min:0'],
        ]);
        return $this->success($this->customerService->update($id, $data));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->customerService->delete($id);
        return $this->success(['message' => 'Customer deleted.']);
    }

    public function ledger(int $id): JsonResponse
    {
        return $this->success($this->customerService->getLedger($id));
    }
}
