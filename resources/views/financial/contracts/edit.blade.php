@extends('layouts.app')

@php
$activeDomain = 'financial';
$activeItem = 'contracts';
@endphp

@section('title', 'Edit Contract: ' . $contract->title)

@section('content')
<livewire:contracts.edit-contract :contract="$contract" />
@endsection
