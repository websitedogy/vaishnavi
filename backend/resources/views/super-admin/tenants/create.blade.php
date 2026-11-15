@extends('layouts.super-admin')

@section('title', 'Create Tenant')
@section('page-title', 'Create Tenant')
@section('page-subtitle', 'Register a new news company on the platform')

@section('content')
    <form method="POST" action="{{ route('super-admin.tenants.store') }}" enctype="multipart/form-data">
        @csrf
        @include('super-admin.partials.tenant-form', ['tenantAdmin' => null])
    </form>
@endsection
