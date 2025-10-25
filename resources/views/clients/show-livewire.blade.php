@extends('layouts.app')

@section('title', $client->name . ' - Client Overview')

@php
$sidebarContext = 'clients';
$activeDomain = 'clients';
$activeSection = 'client-info';
@endphp

@section('content')
    <livewire:clients.client-show :client-id="$client->id" />
@endsection