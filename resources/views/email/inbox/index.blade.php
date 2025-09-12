@extends('layouts.app')

@section('title', 'Email Inbox')

@section('content')
@php
    $sidebarContext = 'email';
@endphp

<livewire:email.inbox />
@endsection
