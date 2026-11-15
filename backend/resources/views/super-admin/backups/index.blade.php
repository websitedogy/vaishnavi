@extends('layouts.super-admin')

@section('title', 'Backup & Restore')
@section('page-title', 'Backup & Restore')
@section('page-subtitle', 'Automatic backups for database and files')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-1 bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-3">Create Backup</h2>
        <form method="POST" action="{{ route('super-admin.backups.store') }}" class="space-y-3">
            @csrf
            <select name="type" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="database">Database Backup</option>
                <option value="files">File Backup</option>
                <option value="full">Full Backup</option>
            </select>
            <button class="w-full py-2.5 rounded-lg bg-brand-600 text-white text-sm font-semibold">Run Backup</button>
        </form>
    </div>
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-900 mb-3">Restore System</h2>
        <p class="text-sm text-slate-500">Select a previous backup and click restore to simulate rollback. This marks restore metadata in the system logs.</p>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Type</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">File</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Size</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Status</th>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse ($backups as $backup)
                <tr>
                    <td class="px-4 py-3 text-sm capitalize">{{ $backup->type }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $backup->file_path }}</td>
                    <td class="px-4 py-3 text-sm">{{ number_format($backup->file_size / 1024 / 1024, 2) }} MB</td>
                    <td class="px-4 py-3 text-sm capitalize">{{ $backup->status }}</td>
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('super-admin.backups.restore', $backup) }}" class="inline">
                            @csrf
                            <button class="text-sm font-semibold text-emerald-600">Restore</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">No backups found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if ($backups->hasPages())
    <div class="mt-4">{{ $backups->links() }}</div>
@endif
@endsection
