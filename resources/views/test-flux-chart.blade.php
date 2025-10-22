<!DOCTYPE html>
<html>
<head>
    <title>Test Flux Chart</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxStyles
</head>
<body class="p-8">
    <h1 class="text-2xl mb-4">Test Flux Chart</h1>
    
    <div class="mb-8">
        <h2 class="text-xl mb-2">Simple Array Test</h2>
        <flux:chart :value="[1, 2, 3, 4, 5]" class="aspect-[3/1]">
            <flux:chart.svg>
                <flux:chart.line class="text-blue-500" />
            </flux:chart.svg>
        </flux:chart>
    </div>

    <div class="mb-8">
        <h2 class="text-xl mb-2">Structured Data Test</h2>
        @php
        $testData = [
            ['date' => '2025-10-18', 'visitors' => 267],
            ['date' => '2025-10-19', 'visitors' => 259],
            ['date' => '2025-10-20', 'visitors' => 269],
            ['date' => '2025-10-21', 'visitors' => 280],
        ];
        @endphp
        
        <flux:chart :value="$testData" class="aspect-[3/1]">
            <flux:chart.svg>
                <flux:chart.line field="visitors" class="text-pink-500" />
                <flux:chart.axis axis="x" field="date">
                    <flux:chart.axis.line />
                    <flux:chart.axis.tick />
                </flux:chart.axis>
                <flux:chart.axis axis="y">
                    <flux:chart.axis.grid />
                    <flux:chart.axis.tick />
                </flux:chart.axis>
            </flux:chart.svg>
        </flux:chart>
    </div>

    @fluxScripts
</body>
</html>
