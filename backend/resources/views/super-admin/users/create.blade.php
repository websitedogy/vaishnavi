@extends('layouts.super-admin')
@section('title', 'Add User')
@section('page-title', 'Add Tenant User')
@section('content')
<form method="POST" action="{{ route('super-admin.users.store') }}">@csrf
@php $user = new \App\Models\User(); @endphp
@include('super-admin.users.form')
</form>
@endsection
