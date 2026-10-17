<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cover = $this->coverImagePath();
        $catalogImage = $cover ? null : \App\Support\CatalogImages::product($this->sku, $this->name);
        return ['id' => $this->id, 'category_id' => $this->category_id, 'sku' => $this->sku, 'name' => $this->name, 'description' => $this->description,
            'price_minor' => $this->price_minor, 'selling_price_minor' => $this->sellingPrice(),
            'sale_price_minor' => $this->sale_price_minor, 'sale_starts_at' => $this->sale_starts_at?->toIso8601String(), 'sale_ends_at' => $this->sale_ends_at?->toIso8601String(),
            'stock' => $this->stock, 'low_stock_threshold' => $this->low_stock_threshold, 'active' => $this->active, 'low_stock' => $this->stock <= $this->low_stock_threshold,
            'image_url' => $cover ? '/api/v1/store/product-images/'.basename($cover) : ($catalogImage['url'] ?? null),
            'uploaded_image_url' => $this->image_path ? '/api/v1/store/product-images/'.basename($this->image_path) : null,
            'image_is_illustrative' => $catalogImage !== null,
            'image_credit' => $catalogImage['credit'] ?? null,
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => ['id' => $image->id, 'url' => '/api/v1/store/product-images/'.basename($image->path)])),
            'category' => new CategoryResource($this->whenLoaded('category'))];
    }
}
