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
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048', 'dimensions:min_width=1,min_height=1,max_width=4096,max_height=4096', 'prohibited_if:remove_image,1,true'],
            'remove_image' => ['sometimes', 'boolean'],
            'image_path' => ['prohibited'], 'image_url' => ['prohibited'],
        ];
    }
}
