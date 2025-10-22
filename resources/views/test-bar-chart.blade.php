@extends('layouts.app')

@section('content')
<div class="p-8 space-y-8">
    <h1 class="text-2xl font-bold mb-4">Bar Chart Test</h1>
    
    {{-- Simple Bar Chart (6 elements) --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Simple Bar Chart (6 elements)</flux:heading>
        
        <flux:chart :value="[
            ['month' => 'Jan', 'sales' => 100],
            ['month' => 'Feb', 'sales' => 150],
            ['month' => 'Mar', 'sales' => 120],
            ['month' => 'Apr', 'sales' => 180],
            ['month' => 'May', 'sales' => 140],
            ['month' => 'Jun', 'sales' => 200],
        ]" class="aspect-[2/1] min-h-[400px]">
            <flux:chart.svg>
                <flux:chart.bar field="sales" class="text-blue-500 dark:text-blue-400" />
                
                <flux:chart.axis axis="x" field="month">
                    <flux:chart.axis.line />
                    <flux:chart.axis.tick />
                </flux:chart.axis>
                
                <flux:chart.axis axis="y">
                    <flux:chart.axis.grid />
                    <flux:chart.axis.tick />
                </flux:chart.axis>
                
                <flux:chart.cursor />
            </flux:chart.svg>
            
            <flux:chart.tooltip>
                <flux:chart.tooltip.heading field="month" />
                <flux:chart.tooltip.value field="sales" label="Sales" />
            </flux:chart.tooltip>
        </flux:chart>
    </flux:card>

    {{-- Simple Bar Chart (4 elements) --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Simple Bar Chart (4 elements)</flux:heading>
        
        <flux:chart :value="[
            ['quarter' => 'Q1', 'sales' => 120],
            ['quarter' => 'Q2', 'sales' => 150],
            ['quarter' => 'Q3', 'sales' => 180],
            ['quarter' => 'Q4', 'sales' => 200],
        ]" class="aspect-[2/1] min-h-[400px]">
            <flux:chart.svg>
                <flux:chart.bar field="sales" class="text-blue-500 dark:text-blue-400" />
                
                <flux:chart.axis axis="x" field="quarter">
                    <flux:chart.axis.line />
                    <flux:chart.axis.tick />
                </flux:chart.axis>
                
                <flux:chart.axis axis="y">
                    <flux:chart.axis.grid />
                    <flux:chart.axis.tick />
                </flux:chart.axis>
                
                <flux:chart.cursor />
            </flux:chart.svg>
            
            <flux:chart.tooltip>
                <flux:chart.tooltip.heading field="quarter" />
                <flux:chart.tooltip.value field="sales" label="Sales" />
            </flux:chart.tooltip>
        </flux:chart>
    </flux:card>

</div>
@endsection
