@extends('layouts.app')

@section('title', 'Time Entries')

@php
$pageTitle = 'Time Entries';
$pageSubtitle = 'Manage employee time entries and payroll';
$sidebarContext = 'hr';
$pageActions = [
    ['label' => 'Time Clock', 'href' => route('hr.time-clock.index'), 'icon' => 'clock', 'variant' => 'ghost']
];
@endphp

@section('content')
    <div class="container-fluid">
        @livewire('h-r.employee-time-entry-index')
    </div>
@endsection
