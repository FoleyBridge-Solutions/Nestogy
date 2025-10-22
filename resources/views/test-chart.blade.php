@extends('layouts.app')

@section('content')
<div class="p-8">
    <h1 class="text-2xl mb-4">Flux Chart Test</h1>
    
    <script>
        console.log('=== FLUX DEBUG ===');
        console.log('customElements:', window.customElements);
        
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const charts = document.querySelectorAll('ui-chart');
                console.log('Found ui-chart elements:', charts.length);
                
                charts.forEach((el, i) => {
                    console.log(`Chart ${i}:`, {
                        hasAttribute: el.hasAttribute('value'),
                        value: el.getAttribute('value'),
                        innerHTML: el.innerHTML.length
                    });
                });
            }, 1000);
        });
    </script>
    
    <div class="bg-white p-6 rounded shadow mb-8">
        <h2 class="text-lg mb-4">Simple Test Chart</h2>
        
        <flux:chart :value="[
            ['date' => '2025-10-21', 'value' => 10],
            ['date' => '2025-10-22', 'value' => 20],
            ['date' => '2025-10-23', 'value' => 15],
            ['date' => '2025-10-24', 'value' => 25],
            ['date' => '2025-10-25', 'value' => 30],
        ]" class="aspect-[2/1] min-h-[300px]">
            <flux:chart.svg>
                <flux:chart.line field="value" class="text-blue-500" />
                <flux:chart.axis axis="x" field="date">
                    <flux:chart.axis.tick />
                </flux:chart.axis>
                <flux:chart.axis axis="y">
                    <flux:chart.axis.grid />
                    <flux:chart.axis.tick />
                </flux:chart.axis>
            </flux:chart.svg>
        </flux:chart>
    </div>
</div>
@endsection
