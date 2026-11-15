@extends('layouts.super-admin')
@section('title', 'Edit Advertisement')
@section('page-title', 'Edit Advertisement')
@section('content')
<form method="POST" action="{{ route('super-admin.advertisements.update', $ad) }}">@csrf @method('PUT')
@include('super-admin.advertisements.form')
</form>
@endsection
