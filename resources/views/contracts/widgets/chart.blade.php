@php
    $chartId = 'chart-' . $widget['id'];
    $chartType = $widget['chart_type'] ?? 'line';
    $chartData = $widget['data'] ?? [];
    $chartOptions = $widget['options'] ?? [];
@endphp

<div class="widget-chart-container">
    @if(empty($chartData))
        <div class="text-center text-gray-600 dark:text-gray-400 py-4">
            <i class="fas fa-chart-line fa-3x mb-3"></i>
            <p class="mb-0">No data available</p>
            <small>Configure this widget to display chart data</small>
        </div>
    @else
        <canvas id="{{ $chartId }}" class="widget-chart"></canvas>
        
        @if(isset($widget['show_legend']) && $widget['show_legend'])
            <div class="chart-legend mt-3">
                <div class="flex flex-wrap justify-center gap-3">
                    @foreach($chartData['datasets'] ?? [] as $dataset)
                        <div class="legend-item flex items-center">
                            <div class="legend-color" style="background-color: {{ $dataset['borderColor'] ?? $dataset['backgroundColor'] }}"></div>
                            <span class="legend-label">{{ $dataset['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>

@if(!empty($chartData))
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('{{ $chartId }}');
        if (ctx && window.Chart) {
            const chartData = @json($chartData);
            const chartOptions = @json($chartOptions);
            
            // Default options
            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: {{ isset($widget['show_legend']) && $widget['show_legend'] ? 'true' : 'false' }},
                        position: '{{ $widget['legend_position'] ?? 'top' }}',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        cornerRadius: 6,
                    }
                },
                scales: {
                    x: {
                        display: {{ isset($widget['show_x_axis']) && $widget['show_x_axis'] ? 'true' : 'false' }},
                        grid: {
                            display: {{ isset($widget['show_grid']) && $widget['show_grid'] ? 'true' : 'false' }},
                            color: 'rgba(0,0,0,0.1)',
                        }
                    },
                    y: {
                        display: {{ isset($widget['show_y_axis']) && $widget['show_y_axis'] ? 'true' : 'false' }},
                        grid: {
                            display: {{ isset($widget['show_grid']) && $widget['show_grid'] ? 'true' : 'false' }},
                            color: 'rgba(0,0,0,0.1)',
                        },
                        beginAtZero: {{ isset($widget['begin_at_zero']) && $widget['begin_at_zero'] ? 'true' : 'false' }}
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                },
                animation: {
                    duration: {{ $widget['animation_duration'] ?? 1000 }}
                }
            };

            // Merge custom options
            const finalOptions = Object.assign(defaultOptions, chartOptions);

            new Chart(ctx, {
                type: '{{ $chartType }}',
                data: chartData,
                options: finalOptions
            });
        }
    });
    </script>
    @endpush
@endif

<style>
.widget-chart-container {
    position: relative;
    height: 300px;
}

.widget-chart {
    max-height: 100%;
}

.chart-legend {
    border-top: 1px solid #e9ecef;
    padding-top: 1rem;
}

.legend-item {
    font-size: 0.875rem;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
    margin-right: 0.5rem;
    flex-shrink: 0;
}

.legend-label {
    color: #6c757d;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .widget-chart-container {
        height: 250px;
    }
    
    .chart-legend .flex {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem !important;
    }
}
</style>
