<?php

declare(strict_types=1);

namespace App\Entities;

use JsonSerializable;

class Product implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly int $categoryId,
        public readonly ?int $brandId,
        public readonly int $unitId,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $sku,
        public readonly ?string $barcode,
        public readonly ?string $description,
        public readonly ?string $shortDescription,
        public readonly string $type,
        public readonly float $purchasePrice,
        public readonly float $sellingPrice,
        public readonly float $minSellingPrice,
        public readonly float $vatRate,
        public readonly bool $isVatInclusive,
        public readonly int $reorderPoint,
        public readonly bool $isActive,
        public readonly array $images,
        public readonly array $attributes,
        public readonly ?int $createdBy,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly ?string $categoryName = null,
        public readonly ?string $brandName = null,
        public readonly ?string $unitName = null,
        public readonly ?float $currentStock = null,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            id: (int)$data['id'],
            categoryId: (int)$data['category_id'],
            brandId: isset($data['brand_id']) ? (int)$data['brand_id'] : null,
            unitId: (int)$data['unit_id'],
            name: $data['name'],
            slug: $data['slug'],
            sku: $data['sku'],
            barcode: $data['barcode'] ?? null,
            description: $data['description'] ?? null,
            shortDescription: $data['short_description'] ?? null,
            type: $data['type'],
            purchasePrice: (float)$data['purchase_price'],
            sellingPrice: (float)$data['selling_price'],
            minSellingPrice: (float)$data['min_selling_price'],
            vatRate: (float)$data['vat_rate'],
            isVatInclusive: (bool)($data['is_vat_inclusive'] ?? false),
            reorderPoint: (int)($data['reorder_point'] ?? 0),
            isActive: (bool)($data['is_active'] ?? true),
            images: is_string($data['images'] ?? null) ? (json_decode($data['images'], true) ?? []) : ($data['images'] ?? []),
            attributes: is_string($data['attributes'] ?? null) ? (json_decode($data['attributes'], true) ?? []) : ($data['attributes'] ?? []),
            createdBy: isset($data['created_by']) ? (int)$data['created_by'] : null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            categoryName: $data['category_name'] ?? null,
            brandName: $data['brand_name'] ?? null,
            unitName: $data['unit_name'] ?? null,
            currentStock: isset($data['current_stock']) ? (float)$data['current_stock'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'category_id'       => $this->categoryId,
            'brand_id'          => $this->brandId,
            'unit_id'           => $this->unitId,
            'name'              => $this->name,
            'slug'              => $this->slug,
            'sku'               => $this->sku,
            'barcode'           => $this->barcode,
            'description'       => $this->description,
            'short_description' => $this->shortDescription,
            'type'              => $this->type,
            'purchase_price'    => $this->purchasePrice,
            'selling_price'     => $this->sellingPrice,
            'min_selling_price' => $this->minSellingPrice,
            'vat_rate'          => $this->vatRate,
            'is_vat_inclusive'  => $this->isVatInclusive,
            'reorder_point'     => $this->reorderPoint,
            'is_active'         => $this->isActive,
            'images'            => $this->images,
            'attributes'        => $this->attributes,
            'category_name'     => $this->categoryName,
            'brand_name'        => $this->brandName,
            'unit_name'         => $this->unitName,
            'current_stock'     => $this->currentStock,
            'created_at'        => $this->createdAt,
            'updated_at'        => $this->updatedAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
