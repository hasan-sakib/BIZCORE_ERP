<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductService
{
    public function create(array $data): Product
    {
        $data['sku'] ??= $this->generateSku($data['name']);

        if (Product::where('sku', $data['sku'])->exists()) {
            throw new \InvalidArgumentException("SKU '{$data['sku']}' already in use.");
        }

        $variants = $data['variants'] ?? [];
        unset($data['variants']);

        $product = Product::create($data);

        foreach ($variants as $variant) {
            ProductVariant::create(array_merge($variant, ['product_id' => $product->id]));
        }

        Log::info('Product created.', ['product_id' => $product->id, 'sku' => $product->sku]);

        return $product->load(['category', 'brand', 'unit', 'variants']);
    }

    public function update(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);

        if (!empty($data['sku']) && $data['sku'] !== $product->sku) {
            $conflict = Product::where('sku', $data['sku'])->where('id', '!=', $id)->exists();
            if ($conflict) {
                throw new \InvalidArgumentException("SKU '{$data['sku']}' already in use.");
            }
        }

        $product->update($data);
        Log::info('Product updated.', ['product_id' => $id]);

        return $product->fresh(['category', 'brand', 'unit', 'variants']);
    }

    public function delete(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => false]);
        $product->delete();
        Log::info('Product deleted.', ['product_id' => $id]);
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::with(['category', 'brand', 'unit'])->orderBy('name');

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(fn($q) => $q
                ->where('name', 'like', $term)
                ->orWhere('sku', 'like', $term)
                ->orWhere('barcode', 'like', $term)
            );
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (!empty($filters['low_stock'])) {
            $query->whereColumn('stock_quantity', '<=', 'reorder_point');
        }

        return $query->paginate($perPage);
    }

    public function generateSku(string $name): string
    {
        $base  = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?? 'PROD', 0, 6));
        $count = Product::where('sku', 'like', "{$base}%")->count();
        return $base . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
