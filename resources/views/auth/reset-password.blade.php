@extends('layouts.auth-standalone')

@section('title', 'Reset Password')
@section('heading', 'Set a new password')

@section('content')
    <livewire:auth.reset-password :token="$request->route('token')" />
@endsection
