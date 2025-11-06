@extends('layouts.app')

@section('content')
    @livewire('tickets.ticket-show', ['ticket' => $ticket])
@endsection