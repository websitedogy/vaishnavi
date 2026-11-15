@php
    $colors = [
        'active' => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
        'trial' => 'bg-sky-100 text-sky-800 ring-sky-600/20',
        'suspended' => 'bg-rose-100 text-rose-800 ring-rose-600/20',
        'expired' => 'bg-amber-100 text-amber-800 ring-amber-600/20',
    ];
    $value = $status instanceof \App\Enums\TenantStatus ? $status->value : $status;
    $label = $status instanceof \App\Enums\TenantStatus ? $status->label() : ucfirst($value);
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $colors[$value] ?? 'bg-slate-100 text-slate-700' }}">
    {{ $label }}
</span>
