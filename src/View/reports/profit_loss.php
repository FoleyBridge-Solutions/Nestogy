<?php
// src/View/reports/tableWithChart.php

$card_title = $card['title'];

$table_header_rows = $table['header_rows'];
$table_body_rows = $table['body_rows'];

if (isset($action)) {
    $action_title = $action['title'];
    if (isset($action['modal'])) {
        $action_modal = $action['modal'];
    }
    if (isset($action['url'])) {
        $action_url = $action['url'];
    }
}

// Process the report data
$total_income = 0;
$total_expense = 0;
$table_body_rows = [];

foreach ($data['report'] as $row) {
    $income = $row['total_income'];
    $expense = $row['total_expense'];
    $profit = $income - $expense;
    $total_income += $income;
    $total_expense += $expense;

    $table_body_rows[] = [
        $row['category_name'],
        number_format($income, 2),
        number_format($expense, 2),
        number_format($profit, 2)
    ];
}

$total_profit = $total_income - $total_expense;

// Update table headers
$table_header_rows = ['Category', 'Income', 'Expense', 'Profit'];

// Add total row
$table_body_rows[] = [
    'TOTAL',
    number_format($total_income, 2),
    number_format($total_expense, 2),
    number_format($total_profit, 2)
];

// Prepare chart data
$chart = [
    'labels' => array_column($data['report'], 'category_name'),
    'datasets' => [
        [
            'label' => 'Income',
            'data' => array_column($data['report'], 'total_income'),
            'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
            'borderColor' => 'rgba(75, 192, 192, 1)',
            'borderWidth' => 1
        ],
        [
            'label' => 'Expense',
            'data' => array_column($data['report'], 'total_expense'),
            'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
            'borderColor' => 'rgba(255, 99, 132, 1)',
            'borderWidth' => 1
        ]
    ]
];

?>

<div class="card">
    <div class="card-header header-elements">
        <h5 class="card-header-title"><?= $card_title ?></h5>
        <div class="card-header-elements ms-auto">
            <!-- Card Action -->
            <?php if (isset($action_url)) : ?>
                <a href="<?= $action_url ?>" class="btn btn-primary">
                    <?= $action_title ?>
                </a>
            <?php endif; ?>
            <?php if (isset($action_modal)) : ?>
                <button type="button" class="btn btn-primary loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="<?= $action_modal ?>">
                    <?= $action_title ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="card-datatable">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <?php foreach ($table_header_rows as $header_row) : ?>
                            <th><?= $header_row ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($table_body_rows as $body_row) : ?>
                        <tr>
                            <?php foreach ($body_row as $cell) : ?>
                                <td><?= $cell ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">

        </div>
    </div>
</div>
<script>
    // Initialize the Chart.js chart
    var ctx = document.getElementById('chart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'bar',
        data: <?= json_encode($chart) ?>,
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Income and Expense by Category'
                }
            }
        }
    });
</script>