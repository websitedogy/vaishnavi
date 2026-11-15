@extends('layouts.super-admin')
@section('title', 'Edit Domain')
@section('page-title', 'Edit Domain')
@section('content')
<form method="POST" action="{{ route('super-admin.domains.update', $domain) }}">@csrf @method('PUT')
@include('super-admin.domains.form')
</form>
@endsection
