@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        @livewire('projects.project-show', ['project' => $project])
    </div>
@endsection