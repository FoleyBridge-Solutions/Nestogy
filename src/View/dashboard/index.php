<div class="row">
    
<h3>Welcome back, <?= $user['user_name'] ?>!</h3>
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
        <div class="card mb-3">
            <div class="card-body">
                <form class="row mb-2" method="get" onchange="this.submit()">
                    <div class="row">
                        <div class="col-3">
                        <h3><?= ucfirst($user['user_role']) ?> Overview for </h3>
                        </div>
                        <div class="col-3">
                            <select name="month" class="form-select">
                                <?php foreach ($time['months'] as $month) { ?>
                                    <option value="<?= $month ?>" <?= $month == $time['month'] ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $month, 1)) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-3">
                            <select name="year" class="form-select">
                                <?php foreach ($time['years'] as $year) { ?>
                                    <option value="<?= $year ?>" <?= $year == $time['year'] ? 'selected' : '' ?>><?= $year ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php if (isset($dashboards['financial'])) { ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h3>Financial Overview</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-4">
                            <div class="card">
                                <div class="card-body">
                                    <p class="card-text">Recievables for <?= date('F Y', strtotime($time['year'] . '-' . $time['month'] . '-01')) ?>: </p>
                                    <span class="badge bg-primary"><?= numfmt_format_currency($formatter, $dashboards['financial']['recievables'], 'USD') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card">
                                <div class="card-body">
                                    <p class="card-text">Income for <?= date('F Y', strtotime($time['year'] . '-' . $time['month'] . '-01')) ?>: </p>
                                    <span class="badge bg-success"><?= numfmt_format_currency($formatter, $dashboards['financial']['income'], 'USD') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card">
                                <div class="card-body">
                                    <p class="card-text">Unbilled Tickets for <?= date('F Y', strtotime($time['year'] . '-' . $time['month'] . '-01')) ?>: </p>
                                    <span class="badge bg-secondary"><?= $dashboards['financial']['unbilled_tickets'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6">
                            <div class="card">
                                <div class="card-body">
                                    <p class="card-text">Income Overview for <?= date('F Y', strtotime($time['year'] . '-' . $time['month'] . '-01')) ?>: </p>
                                    <canvas id="income-chart" width="200" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card">
                                <div class="card-body">
                                    <p class="card-text">Expenses Overview for <?= date('F Y', strtotime($time['year'] . '-' . $time['month'] . '-01')) ?>: </p>
                                    <canvas id="expenses-chart" width="200" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if (isset($dashboards['sales'])) { ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h3>Sales Overview</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-4">
                            <div class="card">
                                <div class="card-body">
                                    <p class="card-text">Quotes for <?= date('F Y', strtotime($time['year'] . '-' . $time['month'] . '-01')) ?>: </p>
                                    <span class="badge bg-primary"><?= numfmt_format_currency($formatter, $dashboards['sales']['total_quotes'], 'USD') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card">
                                <div class="card-body">
                                    <p class="card-text">Quotes Accepted for <?= date('F Y', strtotime($time['year'] . '-' . $time['month'] . '-01')) ?>: </p>
                                    <span class="badge bg-success"><?= numfmt_format_currency($formatter, $dashboards['sales']['total_quotes_accepted'], 'USD') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card">
                                <div class="card-body">
                                    <p class="card-text">New Clients in <?= date('F Y', strtotime($time['year'] . '-' . $time['month'] . '-01')) ?>: </p>
                                    <span class="badge bg-secondary"><?= $dashboards['sales']['new_clients'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if (isset($dashboards['support'])) { ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h3>Support Overview</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Unassigned Tickets</h5>
                                    <p class="card-text">Unassigned Tickets: <?= $dashboards['support']['unassigned_tickets'] ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Assigned Tickets</h5>
                                    <p class="card-text">Assigned Tickets: <?= $dashboards['support']['assigned_tickets'] ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">My Resolved Tickets</h5>
                                    <p class="card-text">Resolved Tickets: <?= $dashboards['support']['resolved_tickets'] ?></p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
    <div class="col-3">
        <div class="card">
            <div class="card-header">
                <h3>Recent Activities</h3>
            </div>
            <div class="card-body">
                <?php foreach ($dashboards['recent_activities'] as $activity) { ?>
                    <div class="card mb-1">
                        <div class="card-body">
                            <h5 class="card-title small"><?= $activity['log_description'] ?></h5>
                            <p class="card-text small text-muted"><?= $activity['user_name'] ?></p> 
                            <p class="card-text small text-muted text-end"><?= date('g:i a - n M, Y', strtotime($activity['log_created_at'])) ?></p>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<!-- Replace ApexCharts with Chart.js -->
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

    // Plugin to draw total in the center of doughnut
    const doughnutLabel = {
        id: 'doughnutLabel',
        afterDatasetsDraw(chart, args, pluginOptions) {
            const { ctx, data } = chart;
            const centerX = chart.getDatasetMeta(0).data[0].x;
            const centerY = chart.getDatasetMeta(0).data[0].y;

            ctx.save();
            ctx.font = 'bold 14px Arial';
            ctx.fillStyle = 'black';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
            ctx.fillText('Total:', centerX, centerY - 15);
            ctx.fillText('$' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}), centerX, centerY + 15);
            ctx.restore();
        }
    };

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
                borderColor: incomeColors.map(color => color.replace(')', ', 0.8)')),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Income by Category'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed !== null) {
                                label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed);
                            }
                            return label;
                        }
                    }
                }
            }
        },
        plugins: [doughnutLabel]
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
                borderColor: expenseColors.map(color => color.replace(')', ', 0.8)')),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Expenses by Category'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed !== null) {
                                label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed);
                            }
                            return label;
                        }
                    }
                }
            }
        },
        plugins: [doughnutLabel]
    });
});
</script>
