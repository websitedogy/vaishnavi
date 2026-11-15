<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ApiUsageLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiManagementController extends Controller
{
    public function index()
    {
        $apiKeys = ApiKey::with('tenant')->latest()->paginate(12, ['*'], 'keys_page');
        $usageLogs = ApiUsageLog::with('tenant', 'apiKey')->latest('requested_at')->limit(20)->get();
        $tenants = Tenant::orderBy('name')->get();

        return view('super-admin.api.index', compact('apiKeys', 'usageLogs', 'tenants'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
        ]);

        ApiKey::create([
            'tenant_id' => $data['tenant_id'] ?? null,
            'name' => $data['name'],
            'key' => 'nsaas_'.Str::random(40),
            'is_active' => true,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return back()->with('success', 'API key generated.');
    }

    public function toggle(ApiKey $apiKey)
    {
        $apiKey->update(['is_active' => ! $apiKey->is_active]);

        return back()->with('success', 'API key status updated.');
    }
}
