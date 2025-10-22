@extends('layouts.app')

@section('title', 'Campaign Performance Analytics')

@php
$pageTitle = 'Campaign Performance';
$pageSubtitle = 'Analyze campaign performance and engagement metrics';
$pageActions = [];
@endphp

@section('content')
    @livewire('marketing.analytics.campaign-performance')
@endsection
