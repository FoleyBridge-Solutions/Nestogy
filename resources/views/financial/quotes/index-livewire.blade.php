@extends('layouts.app')

@section('title', 'Quotes')

@php
$pageTitle = 'Quotes';
$pageSubtitle = 'Manage client quotes and proposals';
$pageActions = [
    [
        'label' => 'New Quote',
        'href' => route('financial.quotes.create'),
        'icon' => 'plus',
        'variant' => 'primary',
    ],
];
@endphp

@section('content')
    @livewire('financial.quote-index')
@endsection
