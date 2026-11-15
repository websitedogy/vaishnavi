@extends('layouts.super-admin')

@section('title', 'Language Management')
@section('page-title', 'Language Management')
@section('page-subtitle', 'Multi-language support: Telugu, English, Hindi')

@section('content')
<div class="max-w-3xl bg-white rounded-xl border border-slate-200 p-6">
    <form method="POST" action="{{ route('super-admin.languages.update') }}" class="space-y-5">
        @csrf
        @method('PUT')
        <div>
            <p class="text-sm font-medium text-slate-700 mb-3">Enabled Languages</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach ($available as $lang)
                    <label class="flex items-center gap-2 p-3 rounded border border-slate-200">
                        <input type="checkbox" name="enabled[]" value="{{ $lang['code'] }}" class="rounded text-brand-600" @checked(in_array($lang['code'], $enabled, true))>
                        <span class="text-sm">{{ $lang['label'] }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Default Language</label>
            <select name="default" class="w-full rounded-lg border-slate-300 text-sm">
                @foreach ($available as $lang)
                    <option value="{{ $lang['code'] }}" @selected($default === $lang['code'])>{{ $lang['label'] }}</option>
                @endforeach
            </select>
        </div>
        <button class="px-5 py-2.5 bg-brand-600 text-white rounded-lg text-sm font-semibold">Save Language Settings</button>
    </form>
</div>
@endsection
