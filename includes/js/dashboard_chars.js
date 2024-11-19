document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('overview-chart').getContext('2d');
    
    // Assuming chart_data is passed from PHP
    const chartData = window.chart_data || {
        labels: [],
        datasets: []
    };

    new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}); 