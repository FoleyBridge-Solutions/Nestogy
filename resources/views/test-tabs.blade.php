@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-2xl mb-4">Tab Test</h1>
    
    <!-- Simple Tab Test -->
    <flux:tab.group>
        <flux:tabs>
            <flux:tab name="tab1">Tab 1</flux:tab>
            <flux:tab name="tab2">Tab 2</flux:tab>
            <flux:tab name="tab3">Tab 3</flux:tab>
        </flux:tabs>
        
        <flux:tab.panel name="tab1">
            <div class="p-4">
                <h2>Content for Tab 1</h2>
                <p>This is the content of the first tab.</p>
            </div>
        </flux:tab.panel>
        
        <flux:tab.panel name="tab2">
            <div class="p-4">
                <h2>Content for Tab 2</h2>
                <p>This is the content of the second tab.</p>
            </div>
        </flux:tab.panel>
        
        <flux:tab.panel name="tab3">
            <div class="p-4">
                <h2>Content for Tab 3</h2>
                <p>This is the content of the third tab.</p>
            </div>
        </flux:tab.panel>
    </flux:tab.group>
</div>
@endsection
