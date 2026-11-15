@extends('layouts.tenant-admin')
@section('page-title','Monetization')
@section('header-actions')<a href="{{ route('tenant-admin.monetization.settings') }}" class="text-sm text-brand-600 font-medium">Paywall settings →</a>@endsection
@section('content')
<div class="grid lg:grid-cols-2 gap-6">
<div><h3 class="font-semibold mb-2">Subscription plans</h3>
<form method="POST" action="{{ route('tenant-admin.monetization.plans.store') }}" class="bg-white border rounded-xl p-5 space-y-2 mb-4">@csrf
<input name="name" required placeholder="Plan name" class="w-full rounded-lg border-slate-300 text-sm">
<input name="price" type="number" step="0.01" required class="w-full rounded-lg border-slate-300 text-sm">
<select name="interval" class="w-full rounded-lg border-slate-300 text-sm"><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select>
<textarea name="features" rows="3" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Features (one per line)"></textarea>
<button class="w-full py-2 bg-brand-600 text-white text-sm rounded-lg">Add plan</button></form>
@foreach($plans as $p)<div class="border rounded-lg p-3 mb-2 text-sm"><strong>{{ $p->name }}</strong> — ${{ $p->price }}/{{ $p->interval }}</div>@endforeach
</div>
<div><h3 class="font-semibold mb-2">Coupon codes</h3>
<form method="POST" action="{{ route('tenant-admin.monetization.coupons.store') }}" class="bg-white border rounded-xl p-5 space-y-2 mb-4">@csrf
<input name="code" required class="w-full rounded-lg border-slate-300 text-sm" placeholder="SAVE20">
<input name="discount_percent" type="number" min="1" max="100" required class="w-full rounded-lg border-slate-300 text-sm">
<input name="expires_at" type="date" class="w-full rounded-lg border-slate-300 text-sm">
<button class="w-full py-2 bg-brand-600 text-white text-sm rounded-lg">Add coupon</button></form>
@foreach($coupons as $c)<div class="border rounded-lg p-3 mb-2 text-sm"><code>{{ $c->code }}</code> — {{ $c->discount_percent }}% off</div>@endforeach
</div></div>
@endsection
