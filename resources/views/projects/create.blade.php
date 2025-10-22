@extends('layouts.app')

@section('title', 'Create Project')

@section('content')
    <livewire:projects.project-create 
        :selectedClientId="$selectedClientId ?? null"
        :selectedTemplateId="$selectedTemplateId ?? null" />
@endsection
