@extends('layouts.app')
@php
$pageTitle = 'Projects';
$pageSubtitle = 'Manage and track your projects';
$pageActions = [['label' => 'Create Project', 'href' => route('projects.create'), 'icon' => 'plus', 'variant' => 'primary']];
@endphp
@section('content')
    <div class="container-fluid">
        @livewire('projects.project-index')
    </div>
@endsection