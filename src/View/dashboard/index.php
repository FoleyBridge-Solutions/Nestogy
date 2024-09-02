<div class="row">
    
<h3>Welcome back, <?= $user['user_name'] ?>!</h3>
    <div class="col-9">
        <?php if (isset($chart_data)) { ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div id="overview-chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        <div class="card mb-3">
            <div class="card-body">
                <h3><?= ucfirst($user['user_role']) ?> Overview for <?= date('F Y', strtotime($time['year'] . '-' . $time['month'] . '-01')) ?></h3>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <form class="row mb-2" method="get" onchange="this.submit()">
                    <div class="row">
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

<!-- Include ApexCharts library -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    var options = {
        series: [{
            name: 'Income',
            type: 'column',
            data: [<?php
                    foreach ($chart_data as $data) {
                        echo $data['income'] . ',';
                    }
                    ?>]
        }, {
            name: 'Expenses',
            type: 'column',
            data: [<?php
                    foreach ($chart_data as $data) {
                        echo $data['expenses'] . ',';
                    }
                    ?>]
        }, {
            name: 'Profit',
            type: 'line',
            data: [<?php
                    foreach ($chart_data as $data) {
                        echo $data['profit'] . ',';
                    }
                    ?>]
        }, {
            name: 'Profit (Estimated)',
            type: 'line',
            data: [<?php
                    foreach ($chart_data as $data) {
                        echo $data['estimated_profit'] . ',';
                    }
                    ?>]
        }, {
            name: 'Profit (LY)',
            type: 'line',
            data: [<?php
                    foreach ($chart_data as $data) {
                        echo $data['last_year_profit'] . ',';
                    }
                    ?>]
        }],
        chart: {
            height: 350,
            type: 'line',
            stacked: false
        },
        colors: ['#32CD32', '#FF0000', '#800080', '#FFA500', '#808080'], // Custom colors
        dataLabels: {
            enabled: false
        },
        stroke: {
            width: [1, 1, 4, 4, 4]
        },
        title: {
            text: 'Financial Overview - YTD <?= $time['year'] ?>',
            align: 'left',
            offsetX: 110
        },
        xaxis: {
            categories: [<?php
                            foreach ($chart_data as $data) {
                                $month = date('M', mktime(0, 0, 0, $data['month'], 1));
                                echo "'" . $month . "',";
                            }
                            ?>],
        },
        yaxis: [{
                seriesName: 'Income',
                axisTicks: {
                    show: true,
                },
                axisBorder: {
                    show: true,
                    color: '#32CD32'
                },
                labels: {
                    style: {
                        colors: '#32CD32',
                    }
                },
                title: {
                    text: "Income ($K)",
                    style: {
                        color: '#32CD32',
                    }
                },
                tooltip: {
                    enabled: true
                }
            },
            {
                seriesName: 'Expenses',
                axisTicks: {
                    show: true,
                },
                axisBorder: {
                    show: true,
                    color: '#FF0000'
                },
                labels: {
                    show: true,
                    style: {
                        colors: '#FF0000',
                    }
                },
                title: {
                    text: "Expenses ($K)",
                    style: {
                        color: '#FF0000',
                    }
                },
            },
            {
                seriesName: 'Profit',
                opposite: true,
                axisTicks: {
                    show: true,
                },
                axisBorder: {
                    show: true,
                    color: '#800080'
                },
                labels: {
                    style: {
                        colors: '#800080',
                    },
                },
                title: {
                    text: "Profit ($K)",
                    style: {
                        color: '#800080',
                    }
                },
            }
        ],
        tooltip: {
            fixed: {
                enabled: true,
                position: 'topLeft', // topRight, topLeft, bottomRight, bottomLeft
                offsetY: 30,
                offsetX: 60
            },
        },
        legend: {
            horizontalAlign: 'left',
            offsetX: 40
        },
        responsive: [{
            breakpoint: 1000,
            options: {
                plotOptions: {
                    bar: {
                        horizontal: true
                    }
                },
                legend: {
                    position: "bottom"
                }
            }
        }]
    };

    var chart = new ApexCharts(document.querySelector("#overview-chart"), options);
    chart.render();
</script>