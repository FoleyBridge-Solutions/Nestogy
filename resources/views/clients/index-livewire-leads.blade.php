@extends('layouts.app')

@section('title', 'Leads')

@section('content')
    <livewire:clients.client-index :show-leads="true" />
@endsection