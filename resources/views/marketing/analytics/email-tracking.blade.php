@extends('layouts.app')

@section('title', 'Email Performance Tracking')

@php
$pageTitle = 'Email Performance';
$pageSubtitle = 'Track email delivery, opens, and clicks';
$pageActions = [];
@endphp

@section('content')
    @livewire('marketing.analytics.email-performance')
@endsection
