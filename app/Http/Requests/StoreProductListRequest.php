<?php

namespace App\Http\Requests;

class StoreProductListRequest extends ListRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'in_stock' => ['sometimes', 'boolean'],
            'min_price_minor' => ['nullable', 'integer', 'min:0', 'max:9007199254740991'],
            'max_price_minor' => array_merge(['nullable', 'integer', 'min:0', 'max:9007199254740991'], $this->filled('min_price_minor') ? ['gte:min_price_minor'] : []),
        ]);
    }
}
