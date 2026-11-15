<div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 mb-6">
    @isset($title)<h2 class="text-base font-semibold text-slate-900 mb-4">{{ $title }}</h2>@endisset
    {{ $slot }}
</div>
