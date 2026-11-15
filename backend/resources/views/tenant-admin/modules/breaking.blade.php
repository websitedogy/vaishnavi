@extends('layouts.tenant-admin')
@section('page-title','Breaking News System')
@section('content')
<form method="POST" action="{{ route('tenant-admin.breaking.update') }}" class="max-w-2xl">@csrf @method('PUT')
<div class="bg-white rounded-xl border p-6 space-y-4">
<label class="flex gap-2 text-sm"><input type="checkbox" name="ticker_enabled" value="1" @checked($settings['ticker_enabled']??true)> Live ticker</label>
<input name="ticker_text" value="{{ $settings['ticker_text'] }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Scrolling ticker text">
<select name="scroll_speed" class="w-full rounded-lg border-slate-300 text-sm"><option value="slow">Slow</option><option value="medium" @selected(($settings['scroll_speed']??'')==='medium')>Medium</option><option value="fast">Fast</option></select>
<label class="flex gap-2 text-sm"><input type="checkbox" name="popup_enabled" value="1" @checked($settings['popup_enabled']??false)> Flash news popup</label>
<textarea name="popup_message" rows="2" class="w-full rounded-lg border-slate-300 text-sm">{{ $settings['popup_message'] }}</textarea>
<label class="block text-sm font-medium">Priority article</label>
<select name="priority_article_id" class="w-full rounded-lg border-slate-300 text-sm"><option value="">—</option>@foreach($articles as $a)<option value="{{ $a->id }}" @selected(($settings['priority_article_id']??'')==$a->id)>{{ $a->title }}</option>@endforeach</select>
<input type="number" name="auto_expiry_hours" value="{{ $settings['auto_expiry_hours'] ?? 24 }}" min="1" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Auto-expiry hours">
<button class="mt-4 px-6 py-2.5 bg-brand-600 text-white font-semibold rounded-lg">Save breaking news</button>
</div>
</form>
@endsection
