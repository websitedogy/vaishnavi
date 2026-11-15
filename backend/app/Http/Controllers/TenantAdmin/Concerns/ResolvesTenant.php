<?php

namespace App\Http\Controllers\TenantAdmin\Concerns;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait ResolvesTenant
{
    protected function user(): User
    {
        return Auth::guard('tenant_admin')->user();
    }

    protected function tenant(): Tenant
    {
        return $this->user()->tenant;
    }

    protected function tenantId(): int
    {
        return (int) $this->user()->tenant_id;
    }

    protected function authorizePermission(string $key): void
    {
        abort_unless($this->user()->hasPermission($key), 403, 'You do not have permission for this action.');
    }
}
