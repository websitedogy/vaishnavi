@extends('layouts.super-admin')

@section('title', 'Domains & SSL')
@section('page-title', 'Domain & Subdomain Management')
@section('page-subtitle', 'Subdomains, custom domains, and auto SSL')

@section('header-actions')
    <a href="{{ route('super-admin.domains.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white text-sm font-semibold rounded-lg hover:bg-brand-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Domain
    </a>
@endsection

@section('content')
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach ([['Subdomains', $stats['subdomains']], ['Custom Domains', $stats['custom']], ['SSL Active', $stats['ssl_active']], ['SSL Pending', $stats['ssl_pending']]] as [$label, $count])
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $count }}</p>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap gap-2 mb-4">
        <a href="{{ route('super-admin.domains.index') }}" class="px-3 py-1.5 rounded-full text-sm font-medium {{ !request('type') ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600' }}">All</a>
        <a href="{{ route('super-admin.domains.index', ['type' => 'subdomain']) }}" class="px-3 py-1.5 rounded-full text-sm font-medium {{ request('type') === 'subdomain' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600' }}">Subdomains</a>
        <a href="{{ route('super-admin.domains.index', ['type' => 'custom']) }}" class="px-3 py-1.5 rounded-full text-sm font-medium {{ request('type') === 'custom' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600' }}">Custom</a>
    </div>

    <form method="GET" class="mb-6">
        @if (request('type'))<input type="hidden" name="type" value="{{ request('type') }}">@endif
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search domains..."
               class="w-full sm:max-w-md rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
    </form>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Domain</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase hidden sm:table-cell">Tenant</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">SSL</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($domains as $domain)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <p class="font-medium text-slate-900">{{ $domain->host }}</p>
                                <p class="text-xs text-slate-500">{{ $domain->fullUrl() }}</p>
                            </td>
                            <td class="px-4 py-4 hidden sm:table-cell text-sm text-slate-600">{{ $domain->tenant->name }}</td>
                            <td class="px-4 py-4">
                                <span class="text-xs font-medium capitalize px-2 py-1 rounded {{ $domain->type === 'subdomain' ? 'bg-sky-100 text-sky-800' : 'bg-violet-100 text-violet-800' }}">{{ $domain->type }}</span>
                            </td>
                            <td class="px-4 py-4">
                                @include('super-admin.partials.ssl-badge', ['status' => $domain->ssl_status])
                                @if ($domain->ssl_auto)<span class="block text-xs text-slate-400 mt-1">Auto SSL</span>@endif
                            </td>
                            <td class="px-4 py-4 text-right whitespace-nowrap">
                                @if ($domain->ssl_status !== \App\Enums\SslStatus::Active)
                                    <form method="POST" action="{{ route('super-admin.domains.ssl', $domain) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-emerald-600 hover:text-emerald-800 px-2 py-1">Generate SSL</button>
                                    </form>
                                @endif
                                <a href="{{ route('super-admin.domains.edit', $domain) }}" class="text-xs font-medium text-brand-600 px-2 py-1">Edit</a>
                                <form method="POST" action="{{ route('super-admin.domains.destroy', $domain) }}" class="inline" onsubmit="return confirm('Delete this domain?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-rose-600 px-2 py-1">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">No domains configured. Base platform: <strong>{{ $baseDomain }}</strong></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($domains->hasPages())<div class="px-4 py-3 border-t">{{ $domains->links() }}</div>@endif
    </div>

    <div class="mt-6 p-4 bg-slate-800 rounded-xl text-slate-300 text-sm">
        <strong class="text-white">Examples:</strong>
        Subdomain → <code class="text-sky-300">clientname.{{ $baseDomain }}</code> ·
        Custom → <code class="text-violet-300">clientnews.com</code>
    </div>
@endsection
