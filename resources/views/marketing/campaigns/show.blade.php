@extends('layouts.app')

@php
$pageTitle = $campaign->name;
$pageSubtitle = 'Campaign Details';
$pageActions = [
    ['label' => 'Edit Campaign', 'href' => route('marketing.campaigns.edit', $campaign), 'icon' => 'pencil', 'variant' => 'primary'],
    ['label' => 'Back to Campaigns', 'href' => route('marketing.campaigns.index'), 'icon' => 'arrow-left', 'variant' => 'ghost']
];
@endphp

@section('content')
    <div class="container-fluid">
        @livewire('marketing.campaign-show', ['campaign' => $campaign, 'metrics' => $metrics])
    </div>
@endsection
