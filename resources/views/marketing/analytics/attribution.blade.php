@extends('layouts.app')

@section('title', 'Attribution Report')

@php
$pageTitle = 'Attribution Report';
$pageSubtitle = 'Lead source attribution and conversion tracking';
$pageActions = [];
@endphp

@section('content')
    @livewire('marketing.analytics.attribution')
@endsection
