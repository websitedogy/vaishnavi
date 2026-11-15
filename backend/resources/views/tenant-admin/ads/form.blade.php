@extends('layouts.tenant-admin')
@section('page-title', $ad->exists ? 'Edit ad' : 'New ad')
@section('content')
<form method="POST" action="{{ $ad->exists ? route('tenant-admin.ads.update',$ad) : route('tenant-admin.ads.store') }}" class="max-w-2xl bg-white rounded-xl border p-6 space-y-4">
@csrf @if($ad->exists)@method('PUT')@endif
<input name="name" value="{{ old('name',$ad->name) }}" required placeholder="Campaign name" class="w-full rounded-lg border-slate-300">
<select name="ad_type" class="w-full rounded-lg border-slate-300 text-sm">@foreach(['banner','sidebar','header','footer','popup','video'] as $t)<option value="{{ $t }}" @selected(old('ad_type',$ad->ad_type)===$t)>{{ ucfirst($t) }}</option>@endforeach</select>
<select name="placement" class="w-full rounded-lg border-slate-300 text-sm">@foreach($placements as $p)<option value="{{ $p->value }}" @selected(old('placement',$ad->placement?->value)===$p->value)>{{ $p->label() }}</option>@endforeach</select>
<input name="image_url" value="{{ old('image_url',$ad->image_url) }}" placeholder="Banner image URL" class="w-full rounded-lg border-slate-300 text-sm">
<input name="video_url" value="{{ old('video_url',$ad->video_url) }}" placeholder="Video ad URL" class="w-full rounded-lg border-slate-300 text-sm">
<input name="link_url" value="{{ old('link_url',$ad->link_url) }}" placeholder="Click URL" class="w-full rounded-lg border-slate-300 text-sm">
<input name="adsense_slot" value="{{ old('adsense_slot',$ad->adsense_slot) }}" placeholder="AdSense slot ID" class="w-full rounded-lg border-slate-300 text-sm">
<textarea name="content" rows="3" placeholder="HTML content" class="w-full rounded-lg border-slate-300 text-sm">{{ old('content',$ad->content) }}</textarea>
<div class="grid grid-cols-2 gap-3"><input type="datetime-local" name="starts_at" value="{{ old('starts_at',$ad->starts_at?->format('Y-m-d\TH:i')) }}" class="rounded-lg border-slate-300 text-sm"><input type="datetime-local" name="ends_at" value="{{ old('ends_at',$ad->ends_at?->format('Y-m-d\TH:i')) }}" class="rounded-lg border-slate-300 text-sm"></div>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$ad->is_active))> Active</label>
<button class="w-full py-2 bg-brand-600 text-white font-semibold rounded-lg">Save</button>
</form>
@endsection
