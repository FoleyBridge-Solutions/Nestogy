@extends('layouts.app')

@section('content')
<div class="p-8 space-y-8">
    <h1 class="text-2xl font-bold mb-4">Marketing Chart Data Test</h1>
    
    @php
    // Simulate exact data from CampaignPerformance component
    $performanceData = [
        ['date' => '2025-10-07', 'campaigns' => 1, 'completed' => 0],
        ['date' => '2025-10-14', 'campaigns' => 1, 'completed' => 0],
        ['date' => '2025-10-15', 'campaigns' => 1, 'completed' => 0],
        ['date' => '2025-10-16', 'campaigns' => 1, 'completed' => 0],
        ['date' => '2025-10-18', 'campaigns' => 1, 'completed' => 0],
        ['date' => '2025-10-20', 'campaigns' => 1, 'completed' => 0],
        ['date' => '2025-10-21', 'campaigns' => 1, 'completed' => 0],
    ];
    
    $engagementData = [
        ['campaign' => 'Test Campaign 1', 'open_rate' => 0.65, 'click_rate' => 0.42],
        ['campaign' => 'Test Campaign 2', 'open_rate' => 0.70, 'click_rate' => 0.35],
        ['campaign' => 'Test Campaign 3', 'open_rate' => 0.60, 'click_rate' => 0.40],
        ['campaign' => 'Test Campaign 4', 'open_rate' => 0.75, 'click_rate' => 0.45],
        ['campaign' => 'Test Campaign 5', 'open_rate' => 0.68, 'click_rate' => 0.38],
    ];
    @endphp
    
    <flux:card>
        <flux:heading size="lg" class="mb-4">Performance Chart (Area + Line)</flux:heading>
        
        <div class="mb-4 p-3 bg-gray-100 rounded text-xs">
            Data Points: {{ count($performanceData) }}<br>
            Sample: {{ json_encode($performanceData[0]) }}
        </div>
        
        <flux:chart :value="$performanceData" class="aspect-[2/1]">
            <flux:chart.svg>
                <flux:chart.area field="campaigns" class="text-blue-200/50 dark:text-blue-400/30" />
                <flux:chart.line field="campaigns" class="text-blue-500 dark:text-blue-400" />
                <flux:chart.line field="completed" class="text-green-500 dark:text-green-400" />
                
                <flux:chart.axis axis="x" field="date">
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
                <flux:chart.tooltip.heading field="date" />
                <flux:chart.tooltip.value field="campaigns" label="Total Campaigns" />
                <flux:chart.tooltip.value field="completed" label="Completed" />
            </flux:chart.tooltip>
        </flux:chart>
    </flux:card>
    
    <flux:card>
        <flux:heading size="lg" class="mb-4">Engagement Chart (Line)</flux:heading>
        
        <div class="mb-4 p-3 bg-gray-100 rounded text-xs">
            Data Points: {{ count($engagementData) }}<br>
            Sample: {{ json_encode($engagementData[0]) }}
        </div>
        
        <flux:chart :value="$engagementData" class="aspect-[2/1]">
            <flux:chart.svg>
                <flux:chart.line field="open_rate" class="text-purple-500 dark:text-purple-400" />
                <flux:chart.line field="click_rate" class="text-orange-500 dark:text-orange-400" />
                
                <flux:chart.axis axis="x" field="campaign">
                    <flux:chart.axis.line />
                    <flux:chart.axis.tick />
                </flux:chart.axis>
                
                <flux:chart.axis axis="y" tick-start="0" tick-end="1" :format="['style' => 'percent', 'minimumFractionDigits' => 0, 'maximumFractionDigits' => 1]">
                    <flux:chart.axis.grid />
                    <flux:chart.axis.tick />
                </flux:chart.axis>
                
                <flux:chart.cursor />
            </flux:chart.svg>
            
            <flux:chart.tooltip>
                <flux:chart.tooltip.heading field="campaign" />
                <flux:chart.tooltip.value field="open_rate" label="Open Rate" :format="['style' => 'percent', 'maximumFractionDigits' => 1]" />
                <flux:chart.tooltip.value field="click_rate" label="Click Rate" :format="['style' => 'percent', 'maximumFractionDigits' => 1]" />
            </flux:chart.tooltip>
        </flux:chart>
    </flux:card>
</div>
@endsection
