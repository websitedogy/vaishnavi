<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\TenantRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::query()->withCount(['users', 'newsPosts']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $tenants = $query->latest()->paginate(12)->withQueryString();

        $statusCounts = collect(TenantStatus::cases())->mapWithKeys(
            fn (TenantStatus $s) => [$s->value => Tenant::where('status', $s)->count()]
        );

        return view('super-admin.tenants.index', compact('tenants', 'statusCounts'));
    }

    public function create()
    {
        return view('super-admin.tenants.create', [
            'tenant' => new Tenant(['status' => TenantStatus::Trial, 'subscription' => 'basic']),
            'subscriptions' => $this->subscriptionPlans(),
        ]);
    }

    public function store(TenantRequest $request)
    {
        $data = $this->prepareData($request);
        $data['slug'] = Tenant::generateSlug($data['name']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('tenants/logos', 'public');
        }

        $tenant = Tenant::create($data);

        $this->syncTenantAdmin($request, $tenant);

        ActivityLogger::log(
            'tenant_created',
            'New tenant created',
            "{$tenant->name} was registered with tenant admin login ({$request->input('admin_email')}).",
            $tenant
        );

        return redirect()
            ->route('super-admin.tenants.index')
            ->with('success', "Tenant \"{$tenant->name}\" created successfully. Tenant admin can log in at /tenant-admin/login.");
    }

    public function edit(Tenant $tenant)
    {
        return view('super-admin.tenants.edit', [
            'tenant' => $tenant,
            'tenantAdmin' => $this->primaryTenantAdmin($tenant),
            'subscriptions' => $this->subscriptionPlans(),
        ]);
    }

    public function update(TenantRequest $request, Tenant $tenant)
    {
        $data = $this->prepareData($request);

        if ($request->hasFile('logo')) {
            if ($tenant->logo) {
                Storage::disk('public')->delete($tenant->logo);
            }
            $data['logo'] = $request->file('logo')->store('tenants/logos', 'public');
        }

        if ($data['status'] !== TenantStatus::Suspended->value) {
            $data['suspended_at'] = null;
        } elseif ($tenant->status !== TenantStatus::Suspended) {
            $data['suspended_at'] = now();
        }

        $tenant->update($data);

        $this->syncTenantAdmin($request, $tenant->fresh());

        ActivityLogger::log(
            'tenant_updated',
            'Tenant updated',
            "{$tenant->name} profile was updated.",
            $tenant
        );

        return redirect()
            ->route('super-admin.tenants.index')
            ->with('success', "Tenant \"{$tenant->name}\" updated successfully.");
    }

    public function destroy(Tenant $tenant)
    {
        $name = $tenant->name;

        if ($tenant->logo) {
            Storage::disk('public')->delete($tenant->logo);
        }

        ActivityLogger::log(
            'tenant_deleted',
            'Tenant removed',
            "{$name} was permanently deleted from the platform."
        );

        $tenant->delete();

        return redirect()
            ->route('super-admin.tenants.index')
            ->with('success', "Tenant \"{$name}\" deleted permanently.");
    }

    public function suspend(Tenant $tenant)
    {
        $tenant->suspend();

        ActivityLogger::log(
            'tenant_suspended',
            'Tenant suspended',
            "{$tenant->name} access has been temporarily disabled.",
            $tenant
        );

        return back()->with('success', "Tenant \"{$tenant->name}\" has been suspended.");
    }

    public function activate(Tenant $tenant)
    {
        $tenant->activate();

        ActivityLogger::log(
            'tenant_activated',
            'Tenant activated',
            "{$tenant->name} access has been restored.",
            $tenant
        );

        return back()->with('success', "Tenant \"{$tenant->name}\" is now active.");
    }

    protected function prepareData(TenantRequest $request): array
    {
        $data = $request->validated();
        unset($data['logo'], $data['admin_name'], $data['admin_email'], $data['admin_password'], $data['admin_password_confirmation']);

        if (! empty($data['domain'])) {
            $data['domain'] = Str::lower(trim($data['domain']));
        }

        $amounts = [
            'basic' => 29,
            'standard' => 79,
            'premium' => 149,
            'enterprise' => 299,
        ];

        if (empty($data['subscription_amount'])) {
            $data['subscription_amount'] = $amounts[$data['subscription']] ?? 0;
        }

        return $data;
    }

    protected function subscriptionPlans(): array
    {
        return [
            'basic' => ['label' => 'Basic', 'price' => 29],
            'standard' => ['label' => 'Standard', 'price' => 79],
            'premium' => ['label' => 'Premium', 'price' => 149],
            'enterprise' => ['label' => 'Enterprise', 'price' => 299],
        ];
    }

    protected function primaryTenantAdmin(Tenant $tenant): ?User
    {
        return $tenant->users()->where('role', UserRole::Admin)->orderBy('id')->first();
    }

    protected function syncTenantAdmin(TenantRequest $request, Tenant $tenant): void
    {
        $admin = $this->primaryTenantAdmin($tenant);
        $isActive = $tenant->status !== TenantStatus::Suspended;

        $payload = [
            'tenant_id' => $tenant->id,
            'name' => $request->input('admin_name') ?: ($tenant->name.' Admin'),
            'email' => $request->input('admin_email'),
            'role' => UserRole::Admin,
            'is_active' => $isActive,
            'permissions' => UserRole::Admin->defaultPermissions(),
        ];

        if ($request->filled('admin_password')) {
            $payload['password'] = Hash::make($request->input('admin_password'));
        }

        if ($admin) {
            if (! $request->filled('admin_password')) {
                unset($payload['password']);
            }
            $admin->update($payload);
        } else {
            $payload['password'] = Hash::make($request->input('admin_password'));
            User::create($payload);
        }
    }
}
