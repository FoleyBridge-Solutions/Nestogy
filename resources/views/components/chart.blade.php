@props([
    'type' => 'line',
    'data' => '{}',
    'options' => '{}',
    'height' => '400',
    'width' => null
])

<div class="chart-component">
    <canvas 
        x-data="chartComponent()" 
        x-ref="canvas" 
        height="{{ $height }}"
        @if($width) width="{{ $width }}" @endif
        x-init="initChart('{{ $type }}', {{ $data }}, {{ $options }})"
    ></canvas>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('chartComponent', () => ({
        chart: null,
        
        initChart(type, data, options) {
            const ctx = this.$refs.canvas.getContext('2d');
            
            // Parse data and options if they're strings
            const chartData = typeof data === 'string' ? JSON.parse(data) : data;
            const chartOptions = typeof options === 'string' ? JSON.parse(options) : options;
            
            // Default options
            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: false
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            };
            
            // Merge options
            const finalOptions = this.mergeDeep(defaultOptions, chartOptions);
            
            this.chart = new Chart(ctx, {
                type: type,
                data: chartData,
                options: finalOptions
            });
        },
        
        updateChart(newData) {
            if (this.chart) {
                this.chart.data = newData;
                this.chart.update();
            }
        },
        
        addData(label, data) {
            if (this.chart) {
                this.chart.data.labels.push(label);
                this.chart.data.datasets.forEach((dataset, index) => {
                    dataset.data.push(data[index] || 0);
                });
                this.chart.update();
            }
        },
        
        removeData() {
            if (this.chart) {
                this.chart.data.labels.pop();
                this.chart.data.datasets.forEach((dataset) => {
                    dataset.data.pop();
                });
                this.chart.update();
            }
        },
        
        destroy() {
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
        },
        
        // Deep merge utility function
        mergeDeep(target, source) {
            const output = Object.assign({}, target);
            if (this.isObject(target) && this.isObject(source)) {
                Object.keys(source).forEach(key => {
                    if (this.isObject(source[key])) {
                        if (!(key in target))
                            Object.assign(output, { [key]: source[key] });
                        else
                            output[key] = this.mergeDeep(target[key], source[key]);
                    } else {
                        Object.assign(output, { [key]: source[key] });
                    }
                });
            }
            return output;
        },
        
        isObject(item) {
            return item && typeof item === 'object' && !Array.isArray(item);
        }
    }));
});
</script>

@push('scripts')
<script>
// Chart.js utility functions
window.ChartUtils = {
    // Generate random colors
    generateColors(count) {
        const colors = [];
        for (let i = 0; i < count; i++) {
            colors.push(`hsl(${Math.floor(Math.random() * 360)}, 70%, 50%)`);
        }
        return colors;
    },
    
    // Common chart configurations
    configs: {
        line: {
            tension: 0.4,
            fill: false,
            borderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6
        },
        bar: {
            borderWidth: 1,
            borderRadius: 4
        },
        pie: {
            borderWidth: 2
        },
        doughnut: {
            borderWidth: 2,
            cutout: '60%'
        }
    },
    
    // Format currency
    formatCurrency(value, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(value);
    },
    
    // Format number
    formatNumber(value) {
        return new Intl.NumberFormat('en-US').format(value);
    }
};
</script>
@endpush