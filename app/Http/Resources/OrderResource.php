<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'number' => $this->number, 'customer_id' => $this->customer_id,
            'status' => $this->status->value, 'subtotal_minor' => $this->subtotal_minor,
            'discount_minor' => $this->discount_minor, 'total_minor' => $this->total_minor,
            'notes' => $this->notes, 'created_at' => $this->created_at->toIso8601String(),
            'customer' => new CustomerResource($this->whenLoaded('customer')), 'items' => $this->whenLoaded('items'),
            'channel' => $this->channel, 'payment_method' => $this->payment_method, 'payment_status' => $this->payment_status,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'shipping' => $this->shipping_name === null ? null : ['name' => $this->shipping_name, 'phone' => $this->shipping_phone, 'address' => $this->shipping_address],
            'allowed_transitions' => array_values(array_map(fn ($status) => $status->value, array_filter(OrderStatus::cases(), fn ($status) => $this->status->canTransitionTo($status)))),
        ];
    }
}
