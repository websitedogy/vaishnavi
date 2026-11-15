<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DomainController extends Controller
{
    public function index(Request $request)
    {
        $query = TenantDomain::with('tenant')->latest();

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('host', 'like', "%{$search}%")
                    ->orWhereHas('tenant', fn ($t) => $t->where('name', 'like', "%{$search}%"));
            });
        }

        $domains = $query->paginate(15)->withQueryString();
        $baseDomain = config('platform.base_domain');
        $stats = [
            'subdomains' => TenantDomain::where('type', 'subdomain')->count(),
            'custom' => TenantDomain::where('type', 'custom')->count(),
            'ssl_active' => TenantDomain::where('ssl_status', 'active')->count(),
            'ssl_pending' => TenantDomain::whereIn('ssl_status', ['pending', 'provisioning'])->count(),
        ];

        return view('super-admin.domains.index', compact('domains', 'baseDomain', 'stats'));
    }

    public function create()
    {
        $tenants = Tenant::orderBy('name')->get();
        $baseDomain = config('platform.base_domain');

        return view('super-admin.domains.create', compact('tenants', 'baseDomain'));
    }

    public function store(Request $request)
    {
        $data = $this->validateDomain($request);
        if (TenantDomain::where('host', $data['host'])->exists()) {
            return back()->withInput()->withErrors(['subdomain' => 'This domain is already in use.']);
        }

        $domain = TenantDomain::create($data);

        if ($domain->ssl_auto) {
            $domain->provisionSsl();
        }

        ActivityLogger::log('domain_created', 'Domain added', "{$domain->host} was configured.", $domain->tenant);

        return redirect()->route('super-admin.domains.index')
            ->with('success', 'Domain configured successfully.');
    }

    public function edit(TenantDomain $domain)
    {
        $tenants = Tenant::orderBy('name')->get();
        $baseDomain = config('platform.base_domain');

        return view('super-admin.domains.edit', compact('domain', 'tenants', 'baseDomain'));
    }

    public function update(Request $request, TenantDomain $domain)
    {
        $data = $this->validateDomain($request, $domain);

        if (TenantDomain::where('host', $data['host'])->where('id', '!=', $domain->id)->exists()) {
            return back()->withInput()->withErrors(['subdomain' => 'This domain is already in use.']);
        }

        $domain->update($data);

        ActivityLogger::log('domain_updated', 'Domain updated', "{$domain->host} settings were changed.", $domain->tenant);

        return redirect()->route('super-admin.domains.index')
            ->with('success', 'Domain updated successfully.');
    }

    public function destroy(TenantDomain $domain)
    {
        $host = $domain->host;
        $tenant = $domain->tenant;
        $domain->delete();

        ActivityLogger::log('domain_deleted', 'Domain removed', "{$host} was removed.", $tenant);

        return back()->with('success', 'Domain removed.');
    }

    public function provisionSsl(TenantDomain $domain)
    {
        $domain->provisionSsl();

        ActivityLogger::log('ssl_provisioned', 'SSL generated', "Auto SSL issued for {$domain->host}.", $domain->tenant);

        return back()->with('success', "SSL certificate generated for {$domain->host}.");
    }

    protected function validateDomain(Request $request, ?TenantDomain $domain = null): array
    {
        $type = $request->input('type');
        $baseDomain = config('platform.base_domain');

        $rules = [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'type' => ['required', Rule::in(['subdomain', 'custom'])],
            'is_primary' => ['boolean'],
            'ssl_auto' => ['boolean'],
        ];

        if ($type === 'subdomain') {
            $rules['subdomain'] = [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
                Rule::unique('tenant_domains', 'host')->ignore($domain?->id),
            ];
        } else {
            $rules['custom_domain'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('tenant_domains', 'host')->ignore($domain?->id),
            ];
        }

        $validated = $request->validate($rules);

        $host = $type === 'subdomain'
            ? Str::lower($validated['subdomain']).'.'.$baseDomain
            : Str::lower($validated['custom_domain']);

        return [
            'tenant_id' => $validated['tenant_id'],
            'type' => $type,
            'host' => $host,
            'is_primary' => $request->boolean('is_primary'),
            'ssl_auto' => $request->boolean('ssl_auto', true),
            'ssl_status' => $request->boolean('ssl_auto', true) ? 'pending' : ($domain?->ssl_status->value ?? 'pending'),
        ];
    }
}
