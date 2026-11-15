@php
    $isEdit = $tenant->exists;
    $statuses = \App\Enums\TenantStatus::cases();
    $tenantAdmin = $tenantAdmin ?? null;
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 sm:p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-5">Company Details</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Company Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $tenant->name) }}" required
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                           placeholder="e.g. Telugu Daily News">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" id="email" value="{{ old('email', $tenant->email) }}" required
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                           placeholder="contact@company.com">
                </div>
                <div>
                    <label for="domain" class="block text-sm font-medium text-slate-700 mb-1.5">Domain</label>
                    <input type="text" name="domain" id="domain" value="{{ old('domain', $tenant->domain) }}"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                           placeholder="news.company.com">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 sm:p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-1">Tenant Admin Login</h2>
            <p class="text-sm text-slate-500 mb-5">
                These credentials are used at
                <a href="{{ route('tenant-admin.login') }}" class="text-brand-600 hover:underline" target="_blank">/tenant-admin/login</a>.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label for="admin_name" class="block text-sm font-medium text-slate-700 mb-1.5">Admin Name</label>
                    <input type="text" name="admin_name" id="admin_name"
                           value="{{ old('admin_name', $tenantAdmin?->name) }}"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                           placeholder="e.g. Rajesh Kumar">
                </div>
                <div class="sm:col-span-2">
                    <label for="admin_email" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Admin Email <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" name="admin_email" id="admin_email" required
                           value="{{ old('admin_email', $tenantAdmin?->email) }}"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                           placeholder="admin@company.com">
                    @error('admin_email')
                        <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="admin_password" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Password @if (! $isEdit)<span class="text-rose-500">*</span>@endif
                    </label>
                    <input type="password" name="admin_password" id="admin_password"
                           @if (! $isEdit) required @endif
                           autocomplete="new-password"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                           placeholder="{{ $isEdit ? 'Leave blank to keep current' : 'Min. 8 characters' }}">
                    @error('admin_password')
                        <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="admin_password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Confirm Password</label>
                    <input type="password" name="admin_password_confirmation" id="admin_password_confirmation"
                           @if (! $isEdit) required @endif
                           autocomplete="new-password"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                           placeholder="Repeat password">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 sm:p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-5">Subscription & Status</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="subscription" class="block text-sm font-medium text-slate-700 mb-1.5">Subscription Plan <span class="text-rose-500">*</span></label>
                    <select name="subscription" id="subscription" required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                        @foreach ($subscriptions as $key => $plan)
                            <option value="{{ $key }}" data-price="{{ $plan['price'] }}" @selected(old('subscription', $tenant->subscription) === $key)>
                                {{ $plan['label'] }} — ${{ $plan['price'] }}/mo
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="subscription_amount" class="block text-sm font-medium text-slate-700 mb-1.5">Monthly Amount ($)</label>
                    <input type="number" name="subscription_amount" id="subscription_amount" step="0.01" min="0"
                           value="{{ old('subscription_amount', $tenant->subscription_amount) }}"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700 mb-1.5">Tenant Status <span class="text-rose-500">*</span></label>
                    <select name="status" id="status" required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $tenant->status?->value) === $status->value)>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="trial_ends_at" class="block text-sm font-medium text-slate-700 mb-1.5">Trial Ends</label>
                    <input type="date" name="trial_ends_at" id="trial_ends_at"
                           value="{{ old('trial_ends_at', $tenant->trial_ends_at?->format('Y-m-d')) }}"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label for="subscription_ends_at" class="block text-sm font-medium text-slate-700 mb-1.5">Subscription Ends</label>
                    <input type="date" name="subscription_ends_at" id="subscription_ends_at"
                           value="{{ old('subscription_ends_at', $tenant->subscription_ends_at?->format('Y-m-d')) }}"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 sm:p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-5">Logo</h2>
            @if ($tenant->logo)
                <img src="{{ asset('storage/'.$tenant->logo) }}" alt="{{ $tenant->name }}" class="w-24 h-24 object-cover rounded-xl border border-slate-200 mb-4">
            @else
                <div class="w-24 h-24 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 mb-4">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            @endif
            <input type="file" name="logo" id="logo" accept="image/*"
                   class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
            <p class="text-xs text-slate-500 mt-2">PNG, JPG up to 2MB</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 sm:p-6">
            <div class="flex flex-col gap-3">
                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-brand-600 text-white text-sm font-semibold rounded-lg hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition">
                    {{ $isEdit ? 'Update Tenant' : 'Create Tenant' }}
                </button>
                <a href="{{ route('super-admin.tenants.index') }}" class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-white text-slate-700 text-sm font-medium rounded-lg border border-slate-300 hover:bg-slate-50 transition">
                    Cancel
                </a>
            </div>
        </div>

        @if ($isEdit && $tenant->isSuspended())
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                This tenant is <strong>suspended</strong> since {{ $tenant->suspended_at?->format('M d, Y') }}.
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('subscription')?.addEventListener('change', function () {
        const price = this.options[this.selectedIndex].dataset.price;
        const amount = document.getElementById('subscription_amount');
        if (amount && !amount.dataset.touched) amount.value = price;
    });
    document.getElementById('subscription_amount')?.addEventListener('input', function () {
        this.dataset.touched = '1';
    });
</script>
@endpush
