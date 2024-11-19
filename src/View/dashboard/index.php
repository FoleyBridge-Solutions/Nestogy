<div class="container-fluid">
    <div class="row">
        <?php if ($components['welcome']) { ?>
            <?php include 'components/welcome.php'; ?>
        <?php } ?>
        
        <div class="col-9">
            <?php if (isset($chart_data)) { ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <canvas id="overview-chart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            
            <?php include 'components/time_selector.php'; ?>
            
            <?php 
            if ($components['financial']) {
                include 'components/financial_overview.php';
                echo '<hr>';
            }
            
            if ($components['sales']) {
                include 'components/sales_overview.php';
                echo '<hr>';
            }
            
            if ($components['support']) {
                include 'components/support_overview.php';
                echo '<hr>';
            }
            ?>
        </div>
        
        <div class="col-3">
            <?php if ($components['recent_activities']) { ?>
                <?php include 'components/recent_activities.php'; ?>
            <?php } ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to generate a color based on a string seed
    function generateColor(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }
        const h = hash % 360;
        const s = 65 + (hash % 20); // 65-85%
        const l = 45 + (hash % 20); // 45-65%
        return `hsl(${h}, ${s}%, ${l}%)`;
    }

    // Function to generate color palette from category names
    function generateColorPalette(categories) {
        return categories.map(category => generateColor(category));
    }

    // Main Overview Chart
    const mainCtx = document.getElementById('overview-chart').getContext('2d');
    new Chart(mainCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($data) { return "'" . date('M', mktime(0, 0, 0, $data['month'], 1)) . "'"; }, $chart_data)); ?>],
            datasets: [{
                label: 'Income',
                data: [<?php echo implode(',', array_column($chart_data, 'income')); ?>],
                backgroundColor: 'rgba(50, 205, 50, 0.5)',
                borderColor: 'rgb(50, 205, 50)',
                borderWidth: 1,
                stack: 'Stack 0'
            }, {
                label: 'Receivables',
                data: [<?php echo implode(',', array_column($chart_data, 'recievables')); ?>],
                backgroundColor: 'rgba(0, 0, 255, 0.5)',
                borderColor: 'rgb(0, 0, 255)',
                borderWidth: 1,
                stack: 'Stack 0'
            }, {
                label: 'Expenses',
                data: [<?php echo implode(',', array_column($chart_data, 'expenses')); ?>],
                backgroundColor: 'rgba(255, 0, 0, 0.5)',
                borderColor: 'rgb(255, 0, 0)',
                borderWidth: 1
            }, {
                label: 'Profit',
                data: [<?php echo implode(',', array_column($chart_data, 'profit')); ?>],
                type: 'line',
                borderColor: 'rgb(128, 0, 128)',
                borderWidth: 2,
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Financial Overview - YTD <?= $time['year'] ?>'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                },
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Month'
                    }
                },
                y: {
                    display: true,
                    stacked: true,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount (USD)'
                    },
                }
            }
        }
    });

    // Income Doughnut Chart
    const incomeCategories = <?= json_encode(array_keys($dashboards['financial']['income_categories'])) ?>;
    const incomeData = <?= json_encode(array_values($dashboards['financial']['income_categories'])) ?>;
    const incomeColors = generateColorPalette(incomeCategories);

    const incomeCtx = document.getElementById('income-chart').getContext('2d');
    new Chart(incomeCtx, {
        type: 'doughnut',
        data: {
            labels: incomeCategories,
            datasets: [{
                data: incomeData,
                backgroundColor: incomeColors,
                borderColor: '#fff',
                borderWidth: 2,
                hoverOffset: 10,
                spacing: 3,
                borderRadius: 4,
                weight: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1,
            cutout: '70%',
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 2000,
                easing: 'easeInOutQuart'
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Income by Category',
                    padding: 20
                },
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: true,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${new Intl.NumberFormat('en-US', { 
                                style: 'currency', 
                                currency: 'USD' 
                            }).format(context.parsed)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Expenses Doughnut Chart
    const expenseCategories = <?= json_encode(array_keys($dashboards['financial']['expense_categories'])) ?>;
    const expenseData = <?= json_encode(array_values($dashboards['financial']['expense_categories'])) ?>;
    const expenseColors = generateColorPalette(expenseCategories);

    const expensesCtx = document.getElementById('expenses-chart').getContext('2d');
    new Chart(expensesCtx, {
        type: 'doughnut',
        data: {
            labels: expenseCategories,
            datasets: [{
                data: expenseData,
                backgroundColor: expenseColors,
                borderColor: '#fff',
                borderWidth: 2,
                hoverOffset: 10,
                spacing: 3,
                borderRadius: 4,
                weight: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1,
            cutout: '70%',
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 2000,
                easing: 'easeInOutQuart'
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Expenses by Category',
                    padding: 20
                },
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: true,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${new Intl.NumberFormat('en-US', { 
                                style: 'currency', 
                                currency: 'USD' 
                            }).format(context.parsed)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Profit Trend Chart
    const profitCtx = document.getElementById('profit-trend-chart').getContext('2d');
    
    <?php
    // Get current month and previous 2 months
    $currentMonth = (int)date('m');
    $currentYear = (int)date('Y');
    $last3Months = [];
    $last3MonthsData = [];
    
    for ($i = 0; $i < 3; $i++) {
        $month = $currentMonth - $i;
        $year = $currentYear;
        
        // Handle year rollover
        if ($month <= 0) {
            $month += 12;
            $year--;
        }
        
        $monthLabel = date('M', mktime(0, 0, 0, $month, 1));
        $last3Months[] = "'" . $monthLabel . "'";
        
        // Find the profit data for this month
        $monthData = array_values(array_filter($chart_data, function($data) use ($month) {
            return $data['month'] == $month;
        }));
        
        $profit = !empty($monthData) ? $monthData[0]['profit'] : 0;
        $last3MonthsData[] = $profit;
    }
    
    // Reverse arrays to show oldest to newest
    $last3Months = array_reverse($last3Months);
    $last3MonthsData = array_reverse($last3MonthsData);
    ?>

    new Chart(profitCtx, {
        type: 'line',
        data: {
            labels: [<?php echo implode(',', $last3Months); ?>],
            datasets: [{
                label: 'Profit',
                data: [<?php echo implode(',', array_map(function($value) { return $value * 1000; }, $last3MonthsData)); ?>],
                borderColor: 'rgb(128, 0, 128)',
                backgroundColor: 'rgba(128, 0, 128, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 20,
                    bottom: 20
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return new Intl.NumberFormat('en-US', { 
                                style: 'currency', 
                                currency: 'USD' 
                            }).format(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: false
                },
                y: {
                    display: false,
                    beginAtZero: false
                }
            }
        }
    });
});
</script>

