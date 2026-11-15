@extends('layouts.tenant-admin')
@section('page-title','Authors & Staff')
@section('header-actions')<a href="{{ route('tenant-admin.staff.create') }}" class="px-3 py-2 bg-brand-600 text-white text-sm font-semibold rounded-lg">+ Add staff</a>@endsection
@section('content')
<div class="bg-white rounded-xl border overflow-hidden">
<table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="p-3 text-left">Name</th><th class="p-3">Role</th><th class="p-3">Articles</th><th class="p-3">Status</th><th class="p-3 text-right">Actions</th></tr></thead>
<tbody>@foreach($staff as $m)<tr class="border-t"><td class="p-3 font-medium">{{ $m->name }}<br><span class="text-slate-500 text-xs">{{ $m->email }}</span></td><td class="p-3">{{ $m->role->label() }}</td><td class="p-3">{{ $m->news_posts_count }}</td><td class="p-3">{{ $m->is_active ? 'Active' : 'Inactive' }}</td>
<td class="p-3 text-right"><a href="{{ route('tenant-admin.staff.profile',$m) }}" class="text-brand-600">Profile</a> <a href="{{ route('tenant-admin.staff.edit',$m) }}" class="text-slate-600 ml-2">Edit</a>
@if($m->id !== auth('tenant_admin')->id())<form method="POST" action="{{ route('tenant-admin.staff.destroy',$m) }}" class="inline" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')<button class="text-rose-600 ml-2">Delete</button></form>@endif</td></tr>@endforeach</tbody></table>
</div>
<div class="mt-4">{{ $staff->links() }}</div>
@endsection
