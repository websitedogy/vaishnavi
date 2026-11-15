@extends('layouts.super-admin')
@section('title', 'Add Domain')
@section('page-title', 'Add Domain')
@section('content')
<form method="POST" action="{{ route('super-admin.domains.store') }}">@csrf
@php $domain = new \App\Models\TenantDomain(); @endphp
@include('super-admin.domains.form')
</form>
@endsection
