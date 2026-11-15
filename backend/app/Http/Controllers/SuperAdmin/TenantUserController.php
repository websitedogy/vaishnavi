<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TenantUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('tenant')->whereNotNull('tenant_id');

        if ($tenantId = $request->get('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $tenants = Tenant::orderBy('name')->get();
        $roleCounts = collect(UserRole::cases())->mapWithKeys(
            fn (UserRole $r) => [$r->value => User::where('role', $r)->whereNotNull('tenant_id')->count()]
        );

        return view('super-admin.users.index', compact('users', 'tenants', 'roleCounts'));
    }

    public function create()
    {
        $tenants = Tenant::orderBy('name')->get();
        $roles = UserRole::cases();
        $permissionKeys = ['add_news', 'delete_news', 'publish_news', 'manage_ads'];

        return view('super-admin.users.create', compact('tenants', 'roles', 'permissionKeys'));
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);
        $data['password'] = Hash::make($data['password']);
        $data['permissions'] = $this->buildPermissions($request);

        $user = User::create($data);

        ActivityLogger::log('user_created', 'Tenant user created', "{$user->name} ({$user->role->label()}) added.", $user->tenant);

        return redirect()->route('super-admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        abort_if(! $user->tenant_id, 404);

        $tenants = Tenant::orderBy('name')->get();
        $roles = UserRole::cases();
        $permissionKeys = ['add_news', 'delete_news', 'publish_news', 'manage_ads'];
        $permissions = $user->resolvedPermissions();

        return view('super-admin.users.edit', compact('user', 'tenants', 'roles', 'permissionKeys', 'permissions'));
    }

    public function update(Request $request, User $user)
    {
        abort_if(! $user->tenant_id, 404);

        $data = $this->validateUser($request, $user);
        $data['permissions'] = $this->buildPermissions($request);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        ActivityLogger::log('user_updated', 'Tenant user updated', "{$user->name} profile was updated.", $user->tenant);

        return redirect()->route('super-admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        abort_if(! $user->tenant_id, 404);

        $name = $user->name;
        $tenant = $user->tenant;
        $user->delete();

        ActivityLogger::log('user_deleted', 'Tenant user removed', "{$name} was deleted.", $tenant);

        return back()->with('success', 'User deleted.');
    }

    protected function validateUser(Request $request, ?User $user = null): array
    {
        $rules = [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['boolean'],
        ];

        if (! $user) {
            $rules['password'] = ['required', Password::defaults()];
        } else {
            $rules['password'] = ['nullable', Password::defaults()];
        }

        return $request->validate($rules);
    }

    protected function buildPermissions(Request $request): array
    {
        return [
            'add_news' => $request->boolean('perm_add_news'),
            'delete_news' => $request->boolean('perm_delete_news'),
            'publish_news' => $request->boolean('perm_publish_news'),
            'manage_ads' => $request->boolean('perm_manage_ads'),
        ];
    }
}
