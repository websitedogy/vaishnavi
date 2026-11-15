<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantAdmin\Concerns\ResolvesTenant;
use App\Models\NewsPost;
use App\Models\StaffActivityLog;
use App\Models\User;
use App\Services\StaffActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    use ResolvesTenant;

    public function index(Request $request)
    {
        $this->authorizePermission('manage_staff');
        $staff = User::where('tenant_id', $this->tenantId())
            ->when($request->role, fn ($q, $r) => $q->where('role', $r))
            ->withCount('newsPosts')
            ->latest()
            ->paginate(15);

        return view('tenant-admin.staff.index', compact('staff'));
    }

    public function create()
    {
        $this->authorizePermission('manage_staff');

        return view('tenant-admin.staff.form', [
            'member' => new User(['is_active' => true, 'role' => UserRole::Reporter]),
            'roles' => UserRole::cases(),
            'permissionKeys' => $this->permissionKeys(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('manage_staff');
        $data = $this->validateStaff($request);
        $data['tenant_id'] = $this->tenantId();
        $data['password'] = Hash::make($data['password']);
        $data['permissions'] = $this->buildPermissions($request);
        $user = User::create($data);
        StaffActivityLogger::log('staff_created', "Added staff: {$user->name}", $user);

        return redirect()->route('tenant-admin.staff.index')->with('success', 'Staff member created.');
    }

    public function edit(User $staff)
    {
        $this->ensureTenantStaff($staff);
        $this->authorizePermission('manage_staff');

        return view('tenant-admin.staff.form', [
            'member' => $staff,
            'roles' => UserRole::cases(),
            'permissionKeys' => $this->permissionKeys(),
            'permissions' => $staff->resolvedPermissions(),
        ]);
    }

    public function update(Request $request, User $staff)
    {
        $this->ensureTenantStaff($staff);
        $this->authorizePermission('manage_staff');
        $data = $this->validateStaff($request, $staff->id);
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $data['permissions'] = $this->buildPermissions($request);
        $staff->update($data);
        StaffActivityLogger::log('staff_updated', "Updated staff: {$staff->name}", $staff);

        return redirect()->route('tenant-admin.staff.index')->with('success', 'Staff updated.');
    }

    public function destroy(User $staff)
    {
        $this->ensureTenantStaff($staff);
        $this->authorizePermission('manage_staff');
        abort_if($staff->id === $this->user()->id, 403, 'You cannot delete your own account.');
        $staff->delete();

        return back()->with('success', 'Staff removed.');
    }

    public function profile(User $staff)
    {
        $this->ensureTenantStaff($staff);
        $stats = [
            'articles' => NewsPost::where('tenant_id', $this->tenantId())->where('author_id', $staff->id)->count(),
            'published' => NewsPost::where('tenant_id', $this->tenantId())->where('author_id', $staff->id)->where('status', 'published')->count(),
            'views' => NewsPost::where('tenant_id', $this->tenantId())->where('author_id', $staff->id)->sum('views'),
        ];
        $recent = NewsPost::where('author_id', $staff->id)->latest()->limit(5)->get();
        $activity = StaffActivityLog::where('user_id', $staff->id)->latest()->limit(10)->get();

        return view('tenant-admin.staff.profile', compact('staff', 'stats', 'recent', 'activity'));
    }

    public function logs()
    {
        $logs = StaffActivityLog::with('user:id,name')
            ->where('tenant_id', $this->tenantId())
            ->latest()
            ->paginate(30);

        return view('tenant-admin.staff.logs', compact('logs'));
    }

    public function approveComment(\App\Models\ArticleComment $comment)
    {
        abort_unless($comment->tenant_id === $this->tenantId(), 404);
        $comment->update(['is_approved' => true]);

        return back()->with('success', 'Comment approved.');
    }

    protected function validateStaff(Request $request, ?int $exceptId = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($exceptId)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => 'boolean',
            'password' => [$exceptId ? 'nullable' : 'required', 'string', Password::defaults(), 'confirmed'],
        ];

        return $request->validate($rules) + ['is_active' => $request->boolean('is_active', true)];
    }

    protected function buildPermissions(Request $request): array
    {
        $perms = [];
        foreach ($this->permissionKeys() as $key) {
            $perms[$key] = $request->boolean("perm_{$key}");
        }

        return $perms;
    }

    protected function permissionKeys(): array
    {
        return ['add_news', 'delete_news', 'publish_news', 'manage_ads', 'manage_categories', 'manage_media', 'manage_staff', 'approve_news'];
    }

    protected function ensureTenantStaff(User $staff): void
    {
        abort_unless($staff->tenant_id === $this->tenantId(), 404);
    }
}
