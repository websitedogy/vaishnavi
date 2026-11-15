@extends('layouts.super-admin')

@section('title', 'SaaS Configuration')
@section('page-title', 'SaaS Configuration')
@section('page-subtitle', 'Platform settings, branding, company info, terms/privacy')

@section('content')
<div class="max-w-4xl bg-white rounded-xl border border-slate-200 p-6">
    <form method="POST" action="{{ route('super-admin.saas.update') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Company Name</label>
                <input type="text" name="company_name" value="{{ $settings['company_name'] }}" class="w-full rounded-lg border-slate-300 text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Company Email</label>
                <input type="email" name="company_email" value="{{ $settings['company_email'] }}" class="w-full rounded-lg border-slate-300 text-sm" required>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Brand Primary</label>
                <input type="color" name="brand_primary" value="{{ $settings['brand_primary'] }}" class="w-full h-10 rounded cursor-pointer">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Brand Secondary</label>
                <input type="color" name="brand_secondary" value="{{ $settings['brand_secondary'] }}" class="w-full h-10 rounded cursor-pointer">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Company Logo</label>
            @if ($settings['logo'])
                <img src="{{ asset('storage/'.$settings['logo']) }}" class="w-16 h-16 rounded-lg object-cover border border-slate-200 mb-2" alt="Logo">
            @endif
            <input type="file" name="logo" class="w-full rounded-lg border-slate-300 text-sm" accept="image/*">
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Terms URL</label>
                <input type="url" name="terms_url" value="{{ $settings['terms_url'] }}" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Privacy URL</label>
                <input type="url" name="privacy_url" value="{{ $settings['privacy_url'] }}" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
        </div>

        <button class="px-5 py-2.5 bg-brand-600 text-white rounded-lg text-sm font-semibold">Save SaaS Settings</button>
    </form>
</div>
@endsection
