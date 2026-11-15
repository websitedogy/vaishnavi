<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');
        $tenantId = $tenant?->id;
        $adminUserId = $tenant
            ? $tenant->users()->where('role', UserRole::Admin)->orderBy('id')->value('id')
            : null;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'domain' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('tenants', 'domain')->ignore($tenantId),
            ],
            'subscription' => ['required', 'string', Rule::in(['basic', 'standard', 'premium', 'enterprise'])],
            'subscription_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(TenantStatus::class)],
            'logo' => ['nullable', 'image', 'max:2048'],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
            'admin_name' => ['nullable', 'string', 'max:255'],
            'admin_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($adminUserId),
            ],
            'admin_password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
        ];

        if (! $tenantId || ! $adminUserId) {
            $rules['admin_password'] = ['required', 'string', Password::defaults(), 'confirmed'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'admin_email' => 'tenant admin email',
            'admin_password' => 'tenant admin password',
            'admin_name' => 'tenant admin name',
        ];
    }
}
