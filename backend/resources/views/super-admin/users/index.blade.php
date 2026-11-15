@extends('layouts.super-admin')
@section('title', 'User Management')
@section('page-title', 'User Management')
@section('page-subtitle', 'Admins, editors, reporters & role permissions')

@section('header-actions')
<a href="{{ route('super-admin.users.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white text-sm font-semibold rounded-lg hover:bg-brand-700">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Add User
</a>
@endsection

@section('content')
<div class="grid grid-cols-3 gap-4 mb-6">
    @foreach (\App\Enums\UserRole::cases() as $role)
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
        <p class="text-sm text-slate-500">{{ $role->label() }}s</p>
        <p class="text-2xl font-bold text-slate-900">{{ $roleCounts[$role->value] ?? 0 }}</p>
    </div>
    @endforeach
</div>

<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route('super-admin.users.index') }}" class="px-3 py-1.5 rounded-full text-sm font-medium {{ !request('role') ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200' }}">All</a>
    @foreach (\App\Enums\UserRole::cases() as $role)
    <a href="{{ route('super-admin.users.index', ['role' => $role->value]) }}" class="px-3 py-1.5 rounded-full text-sm font-medium {{ request('role') === $role->value ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200' }}">{{ $role->label() }}</a>
    @endforeach
</div>

<form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
    @if(request('role'))<input type="hidden" name="role" value="{{ request('role') }}">@endif
    <select name="tenant_id" class="rounded-lg border-slate-300 text-sm sm:w-48">
        <option value="">All tenants</option>
        @foreach($tenants as $t)<option value="{{ $t->id }}" @selected(request('tenant_id') == $t->id)>{{ $t->name }}</option>@endforeach
    </select>
    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search users..." class="flex-1 rounded-lg border-slate-300 text-sm">
    <button type="submit" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium">Filter</button>
</form>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">User</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase hidden md:table-cell">Tenant</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Role</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase hidden lg:table-cell">Permissions</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-200">
            @forelse($users as $user)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-4">
                    <p class="font-medium text-slate-900">{{ $user->name }}</p>
                    <p class="text-sm text-slate-500">{{ $user->email }}</p>
                </td>
                <td class="px-4 py-4 hidden md:table-cell text-sm">{{ $user->tenant?->name }}</td>
                <td class="px-4 py-4"><span class="text-xs font-semibold px-2 py-1 rounded capitalize bg-slate-100">{{ $user->role->label() }}</span></td>
                <td class="px-4 py-4 hidden lg:table-cell">
                    <div class="flex flex-wrap gap-1">
                        @foreach($user->resolvedPermissions() as $key => $allowed)
                        @if($allowed)<span class="text-xs px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700">{{ str_replace('_', ' ', $key) }}</span>@endif
                        @endforeach
                    </div>
                </td>
                <td class="px-4 py-4 text-right whitespace-nowrap">
                    <a href="{{ route('super-admin.users.edit', $user) }}" class="text-sm text-brand-600 font-medium">Edit</a>
                    <form method="POST" action="{{ route('super-admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Delete user?')">@csrf @method('DELETE')
                    <button type="submit" class="text-sm text-rose-600 font-medium ml-2">Delete</button></form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">No tenant users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())<div class="px-4 py-3 border-t">{{ $users->links() }}</div>@endif
</div>
@endsection
