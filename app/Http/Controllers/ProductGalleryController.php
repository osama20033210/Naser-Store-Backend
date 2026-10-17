<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProductGalleryController extends Controller
{
    public function store(Request $request, Product $product)
    {
        Gate::authorize('update', $product);
        $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048', 'dimensions:min_width=1,min_height=1,max_width=4096,max_height=4096']]);
        $file = $request->file('image');
        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$file->getMimeType()];
        $path = $file->storeAs('products', Str::uuid().'.'.$extension, 'local');
        abort_unless($path, 500, 'Image storage failed.');
        try {
            $image = DB::transaction(function () use ($product, $path) {
                $record = Product::query()->lockForUpdate()->findOrFail($product->id);
                abort_if($record->images()->count() >= 8, 422, 'A product can have at most eight additional images.');
                return $record->images()->create(['path' => $path, 'position' => $record->images()->max('position') + 1]);
            }, 3);
        } catch (Throwable $error) {
            $this->cleanup($path);
            throw $error;
        }
        return response()->json(['data' => ['id' => $image->id, 'url' => '/api/v1/store/product-images/'.basename($image->path)]], 201);
    }

    public function destroy(Product $product, int $image)
    {
        Gate::authorize('update', $product);
        DB::transaction(function () use ($product, $image) {
            $record = Product::query()->lockForUpdate()->findOrFail($product->id);
            $photo = $record->images()->findOrFail($image);
            $path = $photo->path;
            $photo->delete();
            DB::afterCommit(fn () => $this->cleanup($path));
        }, 3);
        return response()->noContent();
    }

    public function reorder(Request $request, Product $product)
    {
        Gate::authorize('update', $product);
        $data = $request->validate(['ids' => ['required', 'array', 'max:8'], 'ids.*' => ['integer', 'distinct', 'min:1']]);
        DB::transaction(function () use ($product, $data) {
            $record = Product::query()->lockForUpdate()->findOrFail($product->id);
            $existing = $record->images()->pluck('id')->sort()->values()->all();
            $received = collect($data['ids'])->map(fn ($id) => (int) $id)->sort()->values()->all();
            abort_unless($existing === $received, 409, 'Gallery changed. Reload and try again.');
            foreach ($data['ids'] as $position => $id) {
                $record->images()->whereKey($id)->update(['position' => $position]);
            }
        }, 3);
        return response()->noContent();
    }

    private function cleanup(string $path): void
    {
        try {
            if (! Storage::disk('local')->delete($path)) report(new \RuntimeException('Product image cleanup failed.'));
        } catch (Throwable $error) { report($error); }
    }
}
