<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorefrontSettingsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $rules = [
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40', 'regex:/^\+?[\d ()-]{5,40}$/'],
            'contact_address' => ['nullable', 'string', 'max:2000'],
            'contact_hours' => ['nullable', 'string', 'max:500'],
            'featured_product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
        foreach (['en', 'ar'] as $locale) {
            $rules['hero_title_'.$locale] = ['nullable', 'string', 'max:160'];
            $rules['hero_description_'.$locale] = ['nullable', 'string', 'max:600'];
            $rules['policy_text_'.$locale] = ['nullable', 'string', 'max:30000'];
        }
        return $rules;
    }
}
