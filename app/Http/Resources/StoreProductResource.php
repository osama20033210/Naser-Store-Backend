<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'sku' => $this->sku,
            'description' => $this->description, 'price_minor' => $this->price_minor,
            'in_stock' => $this->stock > 0, 'max_order_quantity' => min($this->stock, 100000),
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
