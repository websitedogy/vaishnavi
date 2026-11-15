@extends('layouts.super-admin')

@section('title', 'Tenant Management')
@section('page-title', 'Tenant Management')
@section('page-subtitle', 'Create, edit, suspend, and manage news companies')

@section('header-actions')
    <a href="{{ route('super-admin.tenants.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white text-sm font-semibold rounded-lg hover:bg-brand-700 transition shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span class="hidden sm:inline">Create Tenant</span>
        <span class="sm:hidden">Create</span>
    </a>
@endsection

@section('content')
    {{-- Status filters --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('super-admin.tenants.index') }}"
           class="px-3 py-1.5 rounded-full text-sm font-medium transition {{ !request('status') ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            All ({{ array_sum($statusCounts->all()) }})
        </a>
        @foreach (\App\Enums\TenantStatus::cases() as $status)
            <a href="{{ route('super-admin.tenants.index', ['status' => $status->value]) }}"
               class="px-3 py-1.5 rounded-full text-sm font-medium transition {{ request('status') === $status->value ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                {{ $status->label() }} ({{ $statusCounts[$status->value] ?? 0 }})
            </a>
        @endforeach
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('super-admin.tenants.index') }}" class="mb-6">
        @if (request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by name, email, or domain..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
            </div>
            <button type="submit" class="px-5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50">Search</button>
        </div>
    </form>

    @if ($tenants->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
            <h3 class="text-lg font-semibold text-slate-900 mb-1">No tenants found</h3>
            <p class="text-slate-500 text-sm mb-6">Create your first news company to get started.</p>
            <a href="{{ route('super-admin.tenants.create') }}" class="inline-flex items-center px-4 py-2 bg-brand-600 text-white text-sm font-semibold rounded-lg hover:bg-brand-700">Create Tenant</a>
        </div>
    @else
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Company</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider hidden md:table-cell">Domain</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider hidden lg:table-cell">Subscription</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                            <th class="px-4 sm:px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($tenants as $tenant)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if ($tenant->logo)
                                            <img src="{{ asset('storage/'.$tenant->logo) }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-slate-200 shrink-0">
                                        @else
                                            <div class="w-10 h-10 rounded-lg bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-sm shrink-0">
                                                {{ strtoupper(substr($tenant->name, 0, 2)) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 truncate">{{ $tenant->name }}</p>
                                            <p class="text-sm text-slate-500 truncate">{{ $tenant->email }}</p>
                                            <p class="text-xs text-slate-400 md:hidden">{{ $tenant->users_count }} users · {{ $tenant->news_posts_count }} posts</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4 hidden md:table-cell">
                                    <span class="text-sm text-slate-600">{{ $tenant->domain ?? '—' }}</span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 hidden lg:table-cell">
                                    <span class="text-sm font-medium text-slate-700 capitalize">{{ $tenant->subscription }}</span>
                                    <span class="text-xs text-slate-500 block">${{ number_format($tenant->subscription_amount, 0) }}/mo</span>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    @include('super-admin.partials.status-badge', ['status' => $tenant->status])
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1 flex-wrap">
                                        <a href="{{ route('super-admin.tenants.edit', $tenant) }}"
                                           class="p-2 text-slate-500 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                        @if ($tenant->status === \App\Enums\TenantStatus::Suspended)
                                            <form method="POST" action="{{ route('super-admin.tenants.activate', $tenant) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Activate">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('super-admin.tenants.suspend', $tenant) }}" class="inline" onsubmit="return confirm('Suspend this tenant? Access will be temporarily disabled.');">
                                                @csrf
                                                <button type="submit" class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Suspend">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('super-admin.tenants.destroy', $tenant) }}" class="inline" onsubmit="return confirm('Permanently delete this tenant? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($tenants->hasPages())
                <div class="px-4 sm:px-6 py-4 border-t border-slate-200">
                    {{ $tenants->links() }}
                </div>
            @endif
        </div>
    @endif
@endsection
