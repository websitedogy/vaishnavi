@extends('layouts.tenant-admin')
@section('page-title', $member->exists ? 'Edit staff' : 'Add staff')
@section('content')
<form method="POST" action="{{ $member->exists ? route('tenant-admin.staff.update',$member) : route('tenant-admin.staff.store') }}" class="max-w-lg bg-white rounded-xl border p-6 space-y-4">
@csrf @if($member->exists) @method('PUT') @endif
<input type="text" name="name" value="{{ old('name',$member->name) }}" required class="w-full rounded-lg border-slate-300" placeholder="Full name">
<input type="email" name="email" value="{{ old('email',$member->email) }}" required class="w-full rounded-lg border-slate-300" placeholder="Email">
<select name="role" class="w-full rounded-lg border-slate-300">@foreach($roles as $r)<option value="{{ $r->value }}" @selected(old('role',$member->role?->value)===$r->value)>{{ $r->label() }}</option>@endforeach</select>
<input type="password" name="password" class="w-full rounded-lg border-slate-300" placeholder="{{ $member->exists ? 'New password (optional)' : 'Password' }}">
<input type="password" name="password_confirmation" class="w-full rounded-lg border-slate-300" placeholder="Confirm password">
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$member->is_active))> Active</label>
<div class="border-t pt-4"><p class="text-sm font-semibold mb-2">Custom permissions</p>@foreach($permissionKeys as $key)<label class="flex items-center gap-2 text-sm mb-1"><input type="checkbox" name="perm_{{ $key }}" value="1" @checked(old("perm_{$key}", ($permissions ?? $member->resolvedPermissions())[$key] ?? false))> {{ str_replace('_',' ',ucfirst($key)) }}</label>@endforeach</div>
<button class="w-full py-2 bg-brand-600 text-white font-semibold rounded-lg">Save</button>
</form>
@endsection
