@extends('layouts.super-admin')

@section('title', 'Edit Tenant')
@section('page-title', 'Edit Tenant')
@section('page-subtitle', $tenant->name)

@section('content')
    <form method="POST" action="{{ route('super-admin.tenants.update', $tenant) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('super-admin.partials.tenant-form')
    </form>
@endsection
