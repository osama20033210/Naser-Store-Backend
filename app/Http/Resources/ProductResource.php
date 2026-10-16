<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'category_id' => $this->category_id, 'sku' => $this->sku, 'name' => $this->name, 'description' => $this->description, 'price_minor' => $this->price_minor, 'stock' => $this->stock, 'low_stock_threshold' => $this->low_stock_threshold, 'active' => $this->active, 'low_stock' => $this->stock <= $this->low_stock_threshold, 'image_url' => $this->image_path ? '/api/v1/store/product-images/'.basename($this->image_path) : null, 'category' => new CategoryResource($this->whenLoaded('category'))];
    }
}
