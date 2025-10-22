@extends('layouts.app')

@section('title', 'Email Templates')

@php
$pageTitle = 'Email Templates';
$pageSubtitle = 'Create and manage email templates for campaigns';
$pageActions = [
    [
        'label' => 'Create Template',
        'href' => route('marketing.templates.create'),
        'icon' => 'plus',
        'variant' => 'primary',
    ],
];
@endphp

@section('content')
    @livewire('marketing.template-index')
@endsection
