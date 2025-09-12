@extends('layouts.app')

@section('title', 'Email Accounts')

@section('content')
@php
    $sidebarContext = 'email';
@endphp

@livewire('email.email-accounts-index')
@endsection