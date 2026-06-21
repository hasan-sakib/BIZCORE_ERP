<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseApiController;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductApiController extends BaseApiController
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request): JsonResponse
    {
        return $this->paginate($this->productService->paginate($request->all()));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:200'],
            'category_id'    => ['required', 'integer', 'exists:categories,id'],
            'unit_id'        => ['required', 'integer', 'exists:units,id'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price'     => ['required', 'numeric', 'min:0'],
        ]);

        return $this->created($this->productService->create($data));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success($this->productService->findWithStock($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name'           => ['sometimes', 'string', 'max:200'],
            'purchase_price' => ['sometimes', 'numeric', 'min:0'],
            'sale_price'     => ['sometimes', 'numeric', 'min:0'],
            'reorder_level'  => ['sometimes', 'integer', 'min:0'],
        ]);

        return $this->success($this->productService->update($id, $data));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->productService->delete($id);
        return $this->success(['message' => 'Product deleted.']);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $products = \App\Models\Product::where('is_active', true)
            ->where(fn ($q) => $q->where('name', 'like', "%$query%")->orWhere('sku', 'like', "%$query%"))
            ->with(['unit', 'category'])
            ->limit(20)
            ->get();
        return $this->success($products);
    }
}
