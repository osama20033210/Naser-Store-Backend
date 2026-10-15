<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'product_id' => $this->product_id, 'order_id' => $this->order_id, 'quantity_change' => $this->quantity_change, 'stock_after' => $this->stock_after, 'reason' => $this->reason, 'created_at' => $this->created_at->toIso8601String(), 'product' => new ProductResource($this->whenLoaded('product')), 'actor' => $this->whenLoaded('user', fn () => ['id' => $this->user->id, 'name' => $this->user->name])];
    }
}
