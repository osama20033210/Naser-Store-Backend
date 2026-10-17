<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductPromotionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'sale_price_minor' => ['present', 'nullable', 'integer', 'min:0', 'max:100000000'],
            'sale_starts_at' => ['nullable', 'required_with:sale_price_minor', 'date_format:Y-m-d\TH:i'],
            'sale_ends_at' => ['nullable', 'required_with:sale_price_minor', 'date_format:Y-m-d\TH:i', 'after:sale_starts_at'],
        ];
    }
}
