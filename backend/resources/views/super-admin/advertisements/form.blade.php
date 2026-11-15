@php $isEdit = $ad->exists; @endphp
<div class="max-w-3xl bg-white rounded-xl border border-slate-200 p-6 space-y-4">
    <div class="grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Ad Name</label>
            <input type="text" name="name" value="{{ old('name', $ad->name) }}" required class="w-full rounded-lg border-slate-300 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Placement</label>
            <select name="placement" required class="w-full rounded-lg border-slate-300 text-sm">
                @foreach($placements as $p)<option value="{{ $p->value }}" @selected(old('placement', $ad->placement?->value) === $p->value)>{{ $p->label() }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Scope (Tenant)</label>
            <select name="tenant_id" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">Global (all tenants)</option>
                @foreach($tenants as $t)<option value="{{ $t->id }}" @selected(old('tenant_id', $ad->tenant_id) == $t->id)>{{ $t->name }}</option>@endforeach
            </select>
        </div>
        <div><label class="block text-sm font-medium text-slate-700 mb-1">Advertiser</label><input type="text" name="advertiser" value="{{ old('advertiser', $ad->advertiser) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
        <div><label class="block text-sm font-medium text-slate-700 mb-1">Revenue ($)</label><input type="number" step="0.01" name="revenue" value="{{ old('revenue', $ad->revenue) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
        <div class="sm:col-span-2"><label class="block text-sm font-medium text-slate-700 mb-1">Content / HTML</label><textarea name="content" rows="3" class="w-full rounded-lg border-slate-300 text-sm">{{ old('content', $ad->content) }}</textarea></div>
        <div><label class="block text-sm font-medium text-slate-700 mb-1">Image URL</label><input type="url" name="image_url" value="{{ old('image_url', $ad->image_url) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
        <div><label class="block text-sm font-medium text-slate-700 mb-1">Link URL</label><input type="url" name="link_url" value="{{ old('link_url', $ad->link_url) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
        <div><label class="block text-sm font-medium text-slate-700 mb-1">Impressions</label><input type="number" name="impressions" value="{{ old('impressions', $ad->impressions) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
        <div><label class="block text-sm font-medium text-slate-700 mb-1">Clicks</label><input type="number" name="clicks" value="{{ old('clicks', $ad->clicks) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
        <div><label class="block text-sm font-medium text-slate-700 mb-1">Starts</label><input type="date" name="starts_at" value="{{ old('starts_at', $ad->starts_at?->format('Y-m-d')) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
        <div><label class="block text-sm font-medium text-slate-700 mb-1">Ends</label><input type="date" name="ends_at" value="{{ old('ends_at', $ad->ends_at?->format('Y-m-d')) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
    </div>
    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $ad->is_active ?? true)) class="rounded text-brand-600"><span class="text-sm">Active</span></label>
    <div class="flex gap-3 pt-2">
        <button type="submit" class="px-5 py-2.5 bg-brand-600 text-white text-sm font-semibold rounded-lg hover:bg-brand-700">{{ $isEdit ? 'Update' : 'Create' }} Ad</button>
        <a href="{{ route('super-admin.advertisements.index') }}" class="px-5 py-2.5 border rounded-lg text-sm hover:bg-slate-50">Cancel</a>
    </div>
</div>
