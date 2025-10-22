@extends('layouts.app')

@php
$pageTitle = 'Marketing Campaigns';
$pageSubtitle = 'Create and manage email campaigns and automation sequences';
$pageActions = [
    ['label' => 'Create Campaign', 'href' => route('marketing.campaigns.create'), 'icon' => 'plus', 'variant' => 'primary']
];
@endphp

@section('content')
    <div class="container-fluid">
        @livewire('marketing.campaign-index')
    </div>
@endsection
