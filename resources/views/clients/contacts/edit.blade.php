@extends('layouts.app')

@section('title', 'Edit Contact - ' . $contact->name)

@section('content')
<div class="container-fluid px-4 lg:px-8">
    @livewire('clients.edit-contact', ['contact' => $contact])
</div>
@endsection