<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('store_posts')->ignore($this->route('post')?->id)],
            'title_en' => ['required', 'string', 'max:255'], 'title_ar' => ['nullable', 'string', 'max:255'],
            'excerpt_en' => ['nullable', 'string', 'max:500'], 'excerpt_ar' => ['nullable', 'string', 'max:500'],
            'body_en' => ['required', 'string', 'max:50000'], 'body_ar' => ['nullable', 'string', 'max:50000'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'published' => ['required', 'boolean'],
            'published_at' => ['nullable', 'required_if:published,true,1', 'date_format:Y-m-d\TH:i'],
        ];
    }
}
