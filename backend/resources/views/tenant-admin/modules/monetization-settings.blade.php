@extends('layouts.tenant-admin')
@section('page-title','Paywall & monetization settings')
@section('content')
<form method="POST" action="{{ route('tenant-admin.monetization.settings.update') }}" class="max-w-xl">@csrf @method('PUT')
<div class="bg-white rounded-xl border p-6 space-y-3">
<label class="flex gap-2 text-sm"><input type="checkbox" name="paywall_enabled" value="1" @checked($settings['paywall_enabled']??false)> Paywall management</label>
<label class="flex gap-2 text-sm"><input type="checkbox" name="paid_articles_enabled" value="1" @checked($settings['paid_articles_enabled']??false)> Paid articles</label>
<input name="default_currency" value="{{ $settings['default_currency'] ?? 'USD' }}" class="w-full rounded-lg border-slate-300 text-sm">
<button class="px-6 py-2.5 bg-brand-600 text-white font-semibold rounded-lg">Save</button>
</div>
</form>
@endsection
