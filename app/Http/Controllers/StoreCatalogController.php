<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreCartRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\StoreProductResource;
use App\Models\Category;
use App\Models\Product;

class StoreCatalogController extends Controller
{
    public function categories(ListRequest $request)
    {
        $query = Category::query()->whereHas('products', fn ($products) => $products->where('active', true))
            ->withCount(['products' => fn ($products) => $products->where('active', true)]);
        if ($q = $request->validated('q')) {
            $query->where('name', 'ilike', '%'.$q.'%');
        }

        return CategoryResource::collection($query->orderBy('name')->paginate($request->integer('per_page', 100)));
    }

    public function products(ListRequest $request)
    {
        $query = Product::query()->with('category')->where('active', true);
        if ($q = $request->validated('q')) {
            $query->where(fn ($products) => $products->where('name', 'ilike', '%'.$q.'%')->orWhere('sku', 'ilike', '%'.$q.'%'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        $sort = $request->validated('sort', 'name');
        if (! in_array($sort, ['name', 'price_minor', 'created_at'], true)) {
            $sort = 'name';
        }

        return StoreProductResource::collection($query->orderBy($sort, $request->validated('direction', 'asc'))->orderBy('id')->paginate($request->integer('per_page', 12)));
    }

    public function product(int $product)
    {
        return new StoreProductResource(Product::query()->with('category')->where('active', true)->findOrFail($product));
    }

    /** An advisory live quote; checkout rechecks and locks stock before committing. */
    public function quote(StoreCartRequest $request)
    {
        $items = collect($request->validated('items'));
        $products = Product::query()->with('category')->where('active', true)->whereIn('id', $items->pluck('product_id'))->get()->keyBy('id');
        $total = 0;
        $valid = true;
        $lines = $items->map(function ($item) use ($products, &$total, &$valid) {
            $product = $products->get($item['product_id']);
            $quantity = (int) $item['quantity'];
            $available = $product && $product->stock >= $quantity;
            $valid = $valid && $available;
            $subtotal = $product ? $product->price_minor * $quantity : null;
            $total += $subtotal ?? 0;

            return ['product_id' => (int) $item['product_id'], 'quantity' => $quantity, 'available' => (bool) $available,
                'product' => $product ? new StoreProductResource($product) : null, 'subtotal_minor' => $subtotal];
        });

        return response()->json(['data' => ['items' => $lines, 'total_minor' => $total, 'can_checkout' => $valid, 'currency' => 'USD']]);
    }
}
