<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $price = isset($this->store_price_minor) ? (int) $this->store_price_minor : $this->sellingPrice();
        $cover = $this->coverImagePath();
        $catalogImage = $cover ? null : \App\Support\CatalogImages::product($this->sku, $this->name);
        return [
            'id' => $this->id, 'name' => $this->name, 'sku' => $this->sku,
            'description' => $this->description, 'price_minor' => $price, 'regular_price_minor' => $this->price_minor,
            'promotion_ends_at' => $price < $this->price_minor ? $this->sale_ends_at?->toIso8601String() : null,
            'price_changes_at' => $price < $this->price_minor ? $this->sale_ends_at?->toIso8601String() : ($this->sale_starts_at?->isFuture() ? $this->sale_starts_at->toIso8601String() : null),
            'image_url' => $cover ? '/api/v1/store/product-images/'.basename($cover) : ($catalogImage['url'] ?? null),
            'image_is_illustrative' => $catalogImage !== null,
            'image_credit' => $catalogImage['credit'] ?? null,
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => ['id' => $image->id, 'url' => '/api/v1/store/product-images/'.basename($image->path)])),
            'in_stock' => $this->stock > 0, 'max_order_quantity' => min($this->stock, 100000),
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
