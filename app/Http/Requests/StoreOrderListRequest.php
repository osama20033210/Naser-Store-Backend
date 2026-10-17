<?php

namespace App\Http\Requests;

class StoreOrderListRequest extends ListRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => array_merge(['nullable', 'date_format:Y-m-d'], $this->filled('date_from') ? ['after_or_equal:date_from'] : []),
        ]);
    }
}
