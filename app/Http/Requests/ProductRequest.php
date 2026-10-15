<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:64', Rule::unique('products')->ignore($this->route('product')?->id)],
            'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'],
            'price_minor' => ['required', 'integer', 'min:0', 'max:100000000'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:1000000'], 'active' => ['required', 'boolean'],
            'stock' => ['prohibited'],
        ];
    }
}
