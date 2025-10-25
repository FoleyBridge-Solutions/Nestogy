@extends('layouts.app')

@section('title', 'Time Clock')

@php
$pageTitle = 'Time Clock';
$pageSubtitle = 'Clock in and out to track your work hours';
$sidebarContext = 'hr';
@endphp

@section('content')
    <div class="container mx-auto max-w-4xl py-6">
        @livewire('h-r.time-clock')
    </div>
@endsection
