@php
    $isEdit = isset($domain) && $domain->exists;
    $subdomainPart = $isEdit && $domain->type === 'subdomain' ? explode('.', $domain->host)[0] : old('subdomain');
    $customPart = $isEdit && $domain->type === 'custom' ? $domain->host : old('custom_domain');
@endphp

<div class="max-w-2xl bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Tenant</label>
        <select name="tenant_id" required class="w-full rounded-lg border-slate-300 text-sm">
            @foreach ($tenants as $t)
                <option value="{{ $t->id }}" @selected(old('tenant_id', $domain->tenant_id ?? '') == $t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">Domain Type</label>
        <div class="flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="type" value="subdomain" class="text-brand-600" {{ old('type', $domain->type ?? 'subdomain') === 'subdomain' ? 'checked' : '' }} onchange="toggleDomainType()">
                <span class="text-sm">Subdomain</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="type" value="custom" class="text-brand-600" {{ old('type', $domain->type ?? '') === 'custom' ? 'checked' : '' }} onchange="toggleDomainType()">
                <span class="text-sm">Custom Domain</span>
            </label>
        </div>
    </div>

    <div id="subdomain-field">
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Subdomain</label>
        <div class="flex rounded-lg shadow-sm">
            <input type="text" name="subdomain" value="{{ $subdomainPart }}" placeholder="clientname"
                   class="flex-1 rounded-l-lg border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500">
            <span class="inline-flex items-center px-3 rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 text-slate-500 text-sm">.{{ $baseDomain }}</span>
        </div>
        <p class="text-xs text-slate-500 mt-1">Example: clientname.{{ $baseDomain }}</p>
    </div>

    <div id="custom-field" class="hidden">
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Custom Domain</label>
        <input type="text" name="custom_domain" value="{{ $customPart }}" placeholder="clientnews.com"
               class="w-full rounded-lg border-slate-300 text-sm">
        <p class="text-xs text-slate-500 mt-1">Example: clientnews.com</p>
    </div>

    <div class="flex flex-wrap gap-6">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_primary" value="1" class="rounded text-brand-600" @checked(old('is_primary', $domain->is_primary ?? false))>
            <span class="text-sm text-slate-700">Primary domain</span>
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="ssl_auto" value="1" class="rounded text-brand-600" @checked(old('ssl_auto', $domain->ssl_auto ?? true))>
            <span class="text-sm text-slate-700">Auto SSL generation</span>
        </label>
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="px-5 py-2.5 bg-brand-600 text-white text-sm font-semibold rounded-lg hover:bg-brand-700">{{ $isEdit ? 'Update' : 'Create' }} Domain</button>
        <a href="{{ route('super-admin.domains.index') }}" class="px-5 py-2.5 border border-slate-300 text-sm font-medium rounded-lg hover:bg-slate-50">Cancel</a>
    </div>
</div>

@push('scripts')
<script>
function toggleDomainType() {
    const type = document.querySelector('input[name="type"]:checked')?.value;
    document.getElementById('subdomain-field').classList.toggle('hidden', type !== 'subdomain');
    document.getElementById('custom-field').classList.toggle('hidden', type !== 'custom');
}
toggleDomainType();
</script>
@endpush
