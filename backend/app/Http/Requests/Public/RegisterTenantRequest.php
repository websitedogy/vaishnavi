<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['required', 'email', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'subscription' => ['nullable', 'string', Rule::in(['basic', 'standard', 'premium'])],
            'terms' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'company_name' => 'news site name',
            'company_email' => 'company email',
            'admin_name' => 'your name',
            'admin_email' => 'login email',
            'admin_password' => 'password',
            'terms' => 'terms and conditions',
        ];
    }
}
