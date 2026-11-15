@extends('layouts.tenant-admin')
@section('page-title','Staff activity logs')
@section('content')
<div class="bg-white rounded-xl border overflow-hidden">
<table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="p-3 text-left">When</th><th class="p-3">User</th><th class="p-3">Action</th><th class="p-3 text-left">Description</th></tr></thead>
<tbody>@foreach($logs as $log)<tr class="border-t"><td class="p-3 text-slate-500">{{ $log->created_at->format('M d, H:i') }}</td><td class="p-3">{{ $log->user?->name ?? '—' }}</td><td class="p-3"><code class="text-xs">{{ $log->action }}</code></td><td class="p-3">{{ $log->description }}</td></tr>@endforeach</tbody></table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
