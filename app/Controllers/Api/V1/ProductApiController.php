<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\Api\BaseApiController;
use App\Core\Cache;
use App\Core\Database;
use App\Entities\Product;
use App\Http\Request;

class ProductApiController extends BaseApiController
{
    public function __construct(
        private readonly Database $db,
        private readonly Cache $cache
    ) {}

    public function index(Request $request): void
    {
        [$page, $perPage] = array_values($this->getPaginationParams($request));
        $branchId = $this->getBranchId($request);

        $where = ['p.is_active = 1', 'p.deleted_at IS NULL'];
        $bindings = [];

        if ($search = $request->query('search')) {
            $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
            $term = '%' . $search . '%';
            $bindings = array_merge($bindings, [$term, $term, $term]);
        }
        if ($catId = $request->query('category_id')) {
            $where[] = 'p.category_id = ?';
            $bindings[] = (int)$catId;
        }
        if ($brandId = $request->query('brand_id')) {
            $where[] = 'p.brand_id = ?';
            $bindings[] = (int)$brandId;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $total = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM products p {$whereClause}", $bindings);
        $offset = ($page - 1) * $perPage;

        $sort = in_array($request->query('sort'), ['name','sku','selling_price','created_at']) ? $request->query('sort') : 'name';
        $order = $request->query('order') === 'desc' ? 'DESC' : 'ASC';

        $rows = $this->db->fetchAll(
            "SELECT p.*, c.name AS category_name, b.name AS brand_name, u.name AS unit_name,
                    COALESCE(SUM(i.quantity), 0) AS current_stock
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN units u ON u.id = p.unit_id
             LEFT JOIN inventory i ON i.product_id = p.id
             LEFT JOIN warehouses w ON w.id = i.warehouse_id AND w.branch_id = {$branchId}
             {$whereClause}
             GROUP BY p.id
             ORDER BY p.{$sort} {$order}
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $this->paginated([
            'data'       => array_map(fn($r) => Product::fromArray($r)->toArray(), $rows),
            'pagination' => paginate($total, $page, $perPage),
        ]);
    }

    public function show(Request $request, int $id): void
    {
        $row = $this->db->fetchOne(
            "SELECT p.*, c.name AS category_name, b.name AS brand_name, u.name AS unit_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN units u ON u.id = p.unit_id
             WHERE p.id = ? AND p.deleted_at IS NULL",
            [$id]
        );

        if (!$row) {
            $this->error('Product not found.', 404);
        }

        $variants = $this->db->fetchAll(
            "SELECT * FROM product_variants WHERE product_id = ? AND is_active = 1",
            [$id]
        );

        $stock = $this->db->fetchAll(
            "SELECT w.name AS warehouse, i.quantity, i.reserved_quantity, i.avg_cost
             FROM inventory i JOIN warehouses w ON w.id = i.warehouse_id
             WHERE i.product_id = ?",
            [$id]
        );

        $product = Product::fromArray($row)->toArray();
        $product['variants'] = $variants;
        $product['stock']    = $stock;

        $this->success($product);
    }

    public function store(Request $request): void
    {
        $data = $request->all();
        $required = ['name', 'category_id', 'unit_id', 'selling_price'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->error("Field [{$field}] is required.", 422);
            }
        }

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $data['name']));
        $sku  = $data['sku'] ?? strtoupper(substr(md5($data['name'] . time()), 0, 8));

        $id = $this->db->table('products')->insert([
            'category_id'       => (int)$data['category_id'],
            'brand_id'          => $data['brand_id'] ?? null,
            'unit_id'           => (int)$data['unit_id'],
            'name'              => $data['name'],
            'slug'              => $slug . '-' . substr(md5($slug), 0, 4),
            'sku'               => $sku,
            'barcode'           => $data['barcode'] ?? null,
            'description'       => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'type'              => $data['type'] ?? 'simple',
            'purchase_price'    => (float)($data['purchase_price'] ?? 0),
            'selling_price'     => (float)$data['selling_price'],
            'min_selling_price' => (float)($data['min_selling_price'] ?? $data['selling_price'] * 0.9),
            'vat_rate'          => (float)($data['vat_rate'] ?? 15),
            'is_vat_inclusive'  => (int)($data['is_vat_inclusive'] ?? 0),
            'reorder_point'     => (int)($data['reorder_point'] ?? 0),
            'is_active'         => 1,
            'images'            => json_encode($data['images'] ?? []),
            'attributes'        => json_encode($data['attributes'] ?? []),
            'created_by'        => $this->currentUser($request)?->id ?? 0,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $row = $this->db->table('products')->where('id', $id)->first();
        $this->success(Product::fromArray($row)->toArray(), 'Product created.', 201);
    }

    public function update(Request $request, int $id): void
    {
        $row = $this->db->table('products')->where('id', $id)->where('is_active', 1)->first();
        if (!$row) {
            $this->error('Product not found.', 404);
        }

        $data = $request->all();
        unset($data['id'], $data['sku'], $data['slug'], $data['created_at'], $data['created_by']);
        $data['updated_at'] = now();

        $this->db->table('products')->where('id', $id)->update($data);
        $updated = $this->db->table('products')->where('id', $id)->first();
        $this->success(Product::fromArray($updated)->toArray(), 'Product updated.');
    }

    public function destroy(Request $request, int $id): void
    {
        $row = $this->db->table('products')->where('id', $id)->whereNull('deleted_at')->first();
        if (!$row) {
            $this->error('Product not found.', 404);
        }

        $this->db->table('products')->where('id', $id)->update([
            'deleted_at' => now(),
            'is_active'  => 0,
            'updated_at' => now(),
        ]);
        $this->success(null, 'Product deleted.');
    }

    public function barcode(Request $request, string $barcode): void
    {
        $cacheKey = 'product_barcode_' . md5($barcode);
        $row = $this->cache->remember($cacheKey, 3600, function () use ($barcode) {
            return $this->db->fetchOne(
                "SELECT p.*, c.name AS category_name, u.name AS unit_name
                 FROM products p
                 LEFT JOIN categories c ON c.id = p.category_id
                 LEFT JOIN units u ON u.id = p.unit_id
                 WHERE (p.barcode = ? OR p.sku = ?) AND p.is_active = 1 AND p.deleted_at IS NULL",
                [$barcode, $barcode]
            );
        });

        if (!$row) {
            $this->error('Product not found.', 404);
        }
        $this->success(Product::fromArray($row)->toArray());
    }
}
