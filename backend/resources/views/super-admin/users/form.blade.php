@php $isEdit = isset($user) && $user->exists; $perms = $permissions ?? []; @endphp
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-4">
            <h2 class="font-semibold text-slate-900">User Details</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tenant</label>
                    <select name="tenant_id" required class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach($tenants as $t)<option value="{{ $t->id }}" @selected(old('tenant_id', $user->tenant_id ?? '') == $t->id)>{{ $t->name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1">Name</label><input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1">Email</label><input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1">Password {{ $isEdit ? '(leave blank to keep)' : '' }}</label><input type="password" name="password" {{ $isEdit ? '' : 'required' }} class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                    <select name="role" id="role-select" required class="w-full rounded-lg border-slate-300 text-sm" onchange="applyRoleDefaults()">
                        @foreach($roles as $role)<option value="{{ $role->value }}" @selected(old('role', $user->role?->value ?? 'reporter') === $role->value)>{{ $role->label() }}</option>@endforeach
                    </select>
                </div>
            </div>
            <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) class="rounded text-brand-600"><span class="text-sm">Active user</span></label>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-900 mb-4">Role Permissions</h2>
        <p class="text-xs text-slate-500 mb-4">Control what this user can do</p>
        @php $permLabels = ['add_news' => 'Add news', 'delete_news' => 'Delete news', 'publish_news' => 'Publish news', 'manage_ads' => 'Manage ads']; @endphp
        @foreach($permissionKeys as $key)
        <label class="flex items-center justify-between py-2.5 border-b border-slate-100 last:border-0">
            <span class="text-sm text-slate-700">{{ $permLabels[$key] }}</span>
            <input type="checkbox" name="perm_{{ $key }}" value="1" class="perm-check rounded text-brand-600" data-key="{{ $key }}" @checked(old('perm_'.$key, $perms[$key] ?? false))>
        </label>
        @endforeach
        <div class="mt-6 flex flex-col gap-2">
            <button type="submit" class="w-full py-2.5 bg-brand-600 text-white text-sm font-semibold rounded-lg hover:bg-brand-700">{{ $isEdit ? 'Update' : 'Create' }} User</button>
            <a href="{{ route('super-admin.users.index') }}" class="w-full py-2.5 text-center border border-slate-300 text-sm rounded-lg hover:bg-slate-50">Cancel</a>
        </div>
    </div>
</div>
@push('scripts')
<script>
const roleDefaults = @json(collect(\App\Enums\UserRole::cases())->mapWithKeys(fn($r) => [$r->value => $r->defaultPermissions()]));
function applyRoleDefaults() {
    const role = document.getElementById('role-select').value;
    const perms = roleDefaults[role] || {};
    document.querySelectorAll('.perm-check').forEach(el => { el.checked = !!perms[el.dataset.key]; });
}
</script>
@endpush
