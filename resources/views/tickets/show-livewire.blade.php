@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        @livewire('tickets.ticket-show', ['ticket' => $ticket])
    </div>
@endsection