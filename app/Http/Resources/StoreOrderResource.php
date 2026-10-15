<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'number' => $this->number, 'status' => $this->status->value,
            'created_at' => $this->created_at->toIso8601String(),
            'subtotal_minor' => $this->subtotal_minor, 'discount_minor' => $this->discount_minor, 'total_minor' => $this->total_minor,
            'payment_method' => $this->payment_method, 'payment_status' => $this->payment_status,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'shipping' => $this->shipping_name === null ? null : [
                'name' => $this->shipping_name, 'phone' => $this->shipping_phone, 'address' => $this->shipping_address,
            ],
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id, 'product_id' => $item->product_id, 'name' => $item->name, 'sku' => $item->sku,
                'quantity' => $item->quantity, 'unit_price_minor' => $item->unit_price_minor, 'subtotal_minor' => $item->subtotal_minor,
            ])),
            'can_cancel' => $this->channel === 'storefront' && $this->status === OrderStatus::Pending,
        ];
    }
}
