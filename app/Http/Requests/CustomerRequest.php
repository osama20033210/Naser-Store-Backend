<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:254', Rule::unique('customers')->ignore($this->route('customer')?->id)],
            'phone' => ['nullable', 'string', 'max:40'], 'address' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
