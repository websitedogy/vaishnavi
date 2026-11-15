@php
    $colors = ['pending' => 'bg-slate-100 text-slate-700', 'provisioning' => 'bg-sky-100 text-sky-800', 'active' => 'bg-emerald-100 text-emerald-800', 'failed' => 'bg-rose-100 text-rose-800', 'expired' => 'bg-amber-100 text-amber-800'];
    $val = $status instanceof \App\Enums\SslStatus ? $status->value : $status;
    $label = $status instanceof \App\Enums\SslStatus ? $status->label() : ucfirst($val);
@endphp
<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $colors[$val] ?? $colors['pending'] }}">
    @if ($val === 'active')
        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
    @endif
    {{ $label }}
</span>
