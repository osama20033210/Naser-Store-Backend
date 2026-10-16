<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListRequest;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

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

        return new ProductResource($this->persist($request, new Product)->load('category'));
    }

    public function show(Product $product): ProductResource
    {
        Gate::authorize('view', $product);

        return new ProductResource($product->load('category'));
    }

    public function update(ProductRequest $request, Product $product): ProductResource
    {
        Gate::authorize('update', $product);

        return new ProductResource($this->persist($request, $product)->load('category'));
    }

    public function destroy(Product $product): Response
    {
        Gate::authorize('delete', $product);
        DB::transaction(function () use ($product) {
            $record = Product::query()->lockForUpdate()->findOrFail($product->id);
            abort_if($record->orderItems()->exists() || $record->movements()->exists(), 409, 'Product has history. Deactivate it instead.');
            $image = $record->image_path;
            $record->delete();
            DB::afterCommit(fn () => $this->deleteImage($image));
        });

        return response()->noContent();
    }

    /** Couple the image reference to the product write; retire the old file after commit. */
    private function persist(ProductRequest $request, Product $product): Product
    {
        $id = $product->exists ? $product->id : null;
        $uploaded = null;
        if ($file = $request->file('image')) {
            $extension = match ($file->getMimeType()) {
                'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
                default => throw new RuntimeException('Unsupported product image.'),
            };
            $uploaded = $file->storeAs('products', Str::uuid().'.'.$extension, 'local');
            if (! $uploaded) {
                throw new RuntimeException('The product image could not be stored.');
            }
        }
        try {
            return DB::transaction(function () use ($request, $id, $uploaded) {
                $record = $id !== null ? Product::query()->lockForUpdate()->findOrFail($id) : new Product;
                $previous = $record->image_path;
                $record->fill($request->safe()->except(['image', 'remove_image']));
                if ($uploaded !== null || $request->boolean('remove_image')) {
                    $record->image_path = $uploaded;
                }
                $record->save();
                if ($previous && $previous !== $record->image_path) {
                    DB::afterCommit(fn () => $this->deleteImage($previous));
                }

                return $record;
            }, 3);
        } catch (Throwable $exception) {
            $this->deleteImage($uploaded);
            throw $exception;
        }
    }

    /** Cleanup failure must not report a committed product edit as failed. */
    private function deleteImage(?string $path): void
    {
        if (! $path || ! preg_match('~^products/[0-9a-f-]{36}\.(jpg|png|webp)$~D', $path)) {
            return;
        }
        try {
            if (! Storage::disk('local')->delete($path)) {
                Log::warning('A superseded product image could not be removed.');
            }
        } catch (Throwable $exception) {
            Log::warning('A superseded product image could not be removed.', ['exception' => $exception::class]);
        }
    }
}
