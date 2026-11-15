@extends('layouts.tenant-admin')
@section('page-title','User Management (Readers)')
@section('content')
<form method="POST" action="{{ route('tenant-admin.readers.store') }}" class="bg-white rounded-xl border p-5 mb-6 grid sm:grid-cols-2 gap-3">@csrf
<input name="name" required placeholder="Name" class="rounded-lg border-slate-300 text-sm">
<input name="email" type="email" required placeholder="Email" class="rounded-lg border-slate-300 text-sm">
<input name="password" type="password" placeholder="Password (optional)" class="rounded-lg border-slate-300 text-sm">
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_premium" value="1"> Premium user</label>
<button class="sm:col-span-2 py-2 bg-brand-600 text-white text-sm rounded-lg">Add reader</button>
</form>
<div class="bg-white rounded-xl border overflow-hidden text-sm">
<table class="w-full"><thead class="bg-slate-50"><tr><th class="p-3 text-left">User</th><th class="p-3">Premium</th><th class="p-3">Status</th><th class="p-3"></th></tr></thead>
<tbody>@foreach($readers as $r)<tr class="border-t"><td class="p-3">{{ $r->name }}<br><span class="text-slate-500">{{ $r->email }}</span></td><td class="p-3">{{ $r->is_premium ? 'Yes' : 'No' }}</td><td class="p-3">{{ $r->is_banned ? 'Banned' : 'Active' }}</td>
<td class="p-3"><form method="POST" action="{{ route('tenant-admin.readers.ban',$r) }}">@csrf<button class="text-rose-600">{{ $r->is_banned ? 'Unban' : 'Ban' }}</button></form></td></tr>@endforeach</tbody></table>
</div>
{{ $readers->links() }}
@endsection
