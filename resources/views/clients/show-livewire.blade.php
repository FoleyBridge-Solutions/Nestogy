@extends('layouts.app')

@section('title', $client->name . ' - Client Overview')

@section('content')
    <livewire:clients.client-show :client-id="$client->id" />
@endsection