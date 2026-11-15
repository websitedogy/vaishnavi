@extends('layouts.super-admin')
@section('title', 'Add Advertisement')
@section('page-title', 'Create Advertisement')
@section('content')
<form method="POST" action="{{ route('super-admin.advertisements.store') }}">@csrf
@include('super-admin.advertisements.form')
</form>
@endsection
