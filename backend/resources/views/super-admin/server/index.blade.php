@extends('layouts.super-admin')

@section('title', 'Server Management')
@section('page-title', 'Server Management')
@section('page-subtitle', 'Monitor CPU, RAM, and disk usage')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    @foreach ([
        ['label' => 'CPU Usage', 'value' => $metrics['cpu'], 'color' => 'bg-sky-500'],
        ['label' => 'RAM Usage', 'value' => $metrics['ram'], 'color' => 'bg-violet-500'],
        ['label' => 'Disk Usage', 'value' => $metrics['disk'], 'color' => 'bg-amber-500'],
    ] as $metric)
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-sm text-slate-500">{{ $metric['label'] }}</p>
            <p class="text-3xl font-bold text-slate-900 mt-1">{{ $metric['value'] }}%</p>
            <div class="h-2 bg-slate-100 rounded-full mt-3">
                <div class="h-2 rounded-full {{ $metric['color'] }}" style="width: {{ min($metric['value'], 100) }}%"></div>
            </div>
        </div>
    @endforeach
</div>

<div class="bg-white rounded-xl border border-slate-200 p-5">
    <h2 class="font-semibold text-slate-900 mb-3">Server Monitor</h2>
    <p class="text-sm text-slate-600">Current uptime: <span class="font-semibold text-slate-900">{{ $metrics['uptime'] }}</span></p>
    <p class="text-xs text-slate-500 mt-2">Values auto-calculated from current runtime environment.</p>
</div>
@endsection
