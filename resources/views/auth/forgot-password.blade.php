@extends('layouts.auth-standalone')

@section('title', 'Forgot Password')
@section('heading', 'Reset your password')
@section('subheading', 'Enter your email address and we will send you a link to reset your password.')

@section('content')
    <livewire:auth.forgot-password />
@endsection
