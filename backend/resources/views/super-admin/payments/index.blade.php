@extends('layouts.super-admin')

@section('title', 'Payment Gateway Management')
@section('page-title', 'Payment Gateway Management')
@section('page-subtitle', 'Configure Razorpay and Cashfree integrations')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @forelse ($gateways as $gateway)
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h2 class="font-semibold text-slate-900 mb-4">{{ $gateway->name }}</h2>
            <form method="POST" action="{{ route('super-admin.payments.update', $gateway) }}" class="space-y-3">
                @csrf
                @method('PUT')
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_enabled" value="1" class="rounded text-brand-600" @checked($gateway->is_enabled)>
                    Enable gateway
                </label>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Mode</label>
                    <select name="mode" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="test" @selected($gateway->mode === 'test')>Test</option>
                        <option value="live" @selected($gateway->mode === 'live')>Live</option>
                    </select>
                </div>
                <input type="text" name="merchant_key" value="{{ $gateway->merchant_key }}" placeholder="Merchant Key" class="w-full rounded-lg border-slate-300 text-sm">
                <input type="text" name="secret_key" value="{{ $gateway->secret_key }}" placeholder="Secret Key" class="w-full rounded-lg border-slate-300 text-sm">
                <input type="text" name="webhook_secret" value="{{ $gateway->webhook_secret }}" placeholder="Webhook Secret" class="w-full rounded-lg border-slate-300 text-sm">
                <button class="w-full py-2.5 bg-brand-600 text-white rounded-lg text-sm font-semibold">Save {{ $gateway->name }}</button>
            </form>
        </div>
    @empty
        <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500">No payment gateways found.</div>
    @endforelse
</div>
@endsection
