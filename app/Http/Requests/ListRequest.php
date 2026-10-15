<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'], 'sort' => ['sometimes', Rule::in(['name', 'created_at', 'price_minor', 'stock', 'total_minor'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])], 'category_id' => ['sometimes', 'integer', 'min:1'],
            'customer_id' => ['sometimes', 'integer', 'min:1'], 'product_id' => ['sometimes', 'integer', 'min:1'],
            'active' => ['sometimes', 'boolean'], 'low_stock' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::enum(OrderStatus::class)],
        ];
    }
}
