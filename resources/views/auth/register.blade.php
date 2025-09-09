@extends('layouts.guest')

@section('title', 'Register User')

@section('content')
    <div class="py-8">
        <livewire:auth.register />
    </div>
@endsection
