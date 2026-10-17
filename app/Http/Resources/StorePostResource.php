<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorePostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'slug' => $this->slug, 'title_en' => $this->title_en, 'title_ar' => $this->title_ar,
            'excerpt_en' => $this->excerpt_en, 'excerpt_ar' => $this->excerpt_ar,
            'body_en' => $this->when(array_key_exists('body_en', $this->resource->getAttributes()), fn () => $this->body_en),
            'body_ar' => $this->when(array_key_exists('body_ar', $this->resource->getAttributes()), fn () => $this->body_ar),
            'product_id' => $this->product_id, 'published' => $this->published,
            'published_at' => $this->published_at?->toIso8601String(),
            'image_url' => $this->whenLoaded('product', fn () => $this->product?->active && $this->product?->image_path ? '/api/v1/store/product-images/'.basename($this->product->image_path) : null),
        ];
    }
}
