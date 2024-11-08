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
        <?php if (isset($chart)) : ?>
            <div class="chart-container">
                <canvas id="chart"></canvas>
            </div>
        <?php endif; ?>
        <div class="table-responsive card-datatable">
            <table class="table table-striped table-bordered datatables-basic">
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
    <?php if (isset($chart)) : ?>
        // Initialize the apexcharts chart
        var ctx = document.getElementById('chart')
        var chart = new Chart(ctx, {
            type: 'bar',
            data: <?= json_encode($chart) ?>,
        });
    <?php endif; ?>
</script>