@extends('layouts.app')

@section('title', 'Campaign Enrollments')

@php
$pageTitle = 'Campaign Enrollments';
$pageSubtitle = 'Track and manage campaign enrollments';
$pageActions = [];
@endphp

@section('content')
    @livewire('marketing.enrollment-index')
@endsection
