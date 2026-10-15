<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListRequest;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function index(ListRequest $request)
    {
        Gate::authorize('viewAny', Product::class);
        $query = Product::query()->with('category');
        if ($q = $request->validated('q')) {
            $query->where(fn ($query) => $query->where('name', 'ilike', '%'.$q.'%')->orWhere('sku', 'ilike', '%'.$q.'%'));
        }
        if ($request->has('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }
        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock', '<=', 'low_stock_threshold');
        }
        $sort = $request->validated('sort', 'created_at');
        if (! in_array($sort, ['name', 'created_at', 'price_minor', 'stock'], true)) {
            $sort = 'created_at';
        }

        return ProductResource::collection($query->orderBy($sort, $request->validated('direction', 'desc'))->orderBy('id')->paginate($request->integer('per_page', 15)));
    }

    public function store(ProductRequest $request): ProductResource
    {
        Gate::authorize('create', Product::class);

        return new ProductResource(Product::create($request->validated())->refresh()->load('category'));
    }

    public function show(Product $product): ProductResource
    {
        Gate::authorize('view', $product);

        return new ProductResource($product->load('category'));
    }

    public function update(ProductRequest $request, Product $product): ProductResource
    {
        Gate::authorize('update', $product);
        $product->update($request->validated());

        return new ProductResource($product->load('category'));
    }

    public function destroy(Product $product): Response
    {
        Gate::authorize('delete', $product);
        abort_if($product->orderItems()->exists() || $product->movements()->exists(), 409, 'Product has history. Deactivate it instead.');
        $product->delete();

        return response()->noContent();
    }
}
