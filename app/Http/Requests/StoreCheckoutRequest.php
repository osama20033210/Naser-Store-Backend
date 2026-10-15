<?php

namespace App\Http\Requests;

class StoreCheckoutRequest extends StoreCartRequest
{
    public function rules(): array
    {
        return [...parent::rules(),
            'checkout_key' => ['required', 'uuid'],
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_phone' => ['required', 'string', 'max:40'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'customer_id' => ['prohibited'], 'created_by' => ['prohibited'], 'discount_minor' => ['prohibited'],
            'status' => ['prohibited'], 'payment_method' => ['prohibited'], 'payment_status' => ['prohibited'],
            'subtotal_minor' => ['prohibited'], 'total_minor' => ['prohibited'],
        ];
    }
}
