@extends('layouts.super-admin')
@section('title', 'Edit User')
@section('page-title', 'Edit User')
@section('content')
<form method="POST" action="{{ route('super-admin.users.update', $user) }}">@csrf @method('PUT')
@include('super-admin.users.form')
</form>
@endsection
