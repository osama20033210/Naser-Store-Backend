<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Do not claim a pre-existing offline customer's history by email alone.
            'email' => ['required', 'email', 'max:254', Rule::unique('users'), Rule::unique('customers')],
            'password' => ['required', 'string', 'max:128', 'confirmed', Password::min(12)->letters()->numbers()],
            'roles' => ['prohibited'], 'permissions' => ['prohibited'], 'account_type' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return ['email.unique' => 'This email is unavailable. Sign in or contact the store.'];
    }
}
