@extends('layouts.app')

@section('title', 'Revenue Attribution')

@php
$pageTitle = 'Revenue Attribution';
$pageSubtitle = 'Revenue generated from marketing sources';
$pageActions = [];
@endphp

@section('content')
    @livewire('marketing.analytics.revenue-attribution')
@endsection
