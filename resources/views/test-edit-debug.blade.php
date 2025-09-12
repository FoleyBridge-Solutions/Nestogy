@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Test Edit Client Debug</h1>
    <p>If you can see this, the layout is working.</p>
    
    <!-- Test some basic Flux components -->
    <flux:card>
        <flux:heading>Test Heading</flux:heading>
        <flux:text>Test text content</flux:text>
        
        <flux:field>
            <flux:label>Test Label</flux:label>
            <flux:input value="test value" />
        </flux:field>
    </flux:card>
</div>
@endsection