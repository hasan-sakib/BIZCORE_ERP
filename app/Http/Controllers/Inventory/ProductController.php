<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\BaseController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Unit;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends BaseController
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request): View
    {
        $products   = $this->productService->paginate($request->all());
        $categories = Category::where('status', 'active')->orderBy('name')->get();
        $brands     = Brand::orderBy('name')->get();
        return view('products.index', compact('products', 'categories', 'brands'));
    }

    public function create(): View
    {
        $categories = Category::where('status', 'active')->orderBy('name')->get();
        $brands     = Brand::orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();
        return view('products.create', compact('categories', 'brands', 'units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:200'],
            'sku'           => ['nullable', 'string', 'max:50', 'unique:products,sku'],
            'category_id'   => ['required', 'integer', 'exists:categories,id'],
            'brand_id'      => ['nullable', 'integer', 'exists:brands,id'],
            'unit_id'       => ['required', 'integer', 'exists:units,id'],
            'purchase_price'=> ['required', 'numeric', 'min:0'],
            'sale_price'    => ['required', 'numeric', 'min:0'],
            'vat_rate'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'description'   => ['nullable', 'string'],
            'is_active'     => ['boolean'],
        ]);

        $product = $this->productService->create($data);
        $this->success('Product created successfully.');
        return redirect()->route('products.show', $product->id);
    }

    public function show(int $id): View
    {
        $product = $this->productService->findWithStock($id);
        return view('products.show', compact('product'));
    }

    public function edit(int $id): View
    {
        $product    = $this->productService->find($id);
        $categories = Category::where('status', 'active')->orderBy('name')->get();
        $brands     = Brand::orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();
        return view('products.edit', compact('product', 'categories', 'brands', 'units'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:200'],
            'sku'            => ['nullable', 'string', 'max:50', 'unique:products,sku,' . $id],
            'category_id'    => ['required', 'integer', 'exists:categories,id'],
            'brand_id'       => ['nullable', 'integer', 'exists:brands,id'],
            'unit_id'        => ['required', 'integer', 'exists:units,id'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price'     => ['required', 'numeric', 'min:0'],
            'vat_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reorder_level'  => ['nullable', 'integer', 'min:0'],
            'description'    => ['nullable', 'string'],
            'is_active'      => ['boolean'],
        ]);

        $this->productService->update($id, $data);
        $this->success('Product updated successfully.');
        return redirect()->route('products.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->productService->delete($id);
        $this->success('Product deleted.');
        return redirect()->route('products.index');
    }
}
