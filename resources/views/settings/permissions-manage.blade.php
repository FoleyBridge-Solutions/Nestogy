@extends('layouts.app')

@section('title', 'Permissions Management')

@section('content')
    @livewire('settings.permissions-management', ['tab' => request('tab', 'overview')])
@endsection
