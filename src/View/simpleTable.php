<?php
// src/View/simpleTable.php

$card_title = $card['title'];

$table_header_rows = $table['header_rows'];
$table_body_rows = $table['body_rows'];

if (isset($action)) {
    $action_title = $action['title'];
    $action_button = $action['button'];
    $action_modal = $action['modal'];
}

?>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><?= $card_title ?></h5>

        </div>
        <div class="card-body">
            <?php if (isset($action_title)) : ?>
                <button type="button" class="btn btn-primary loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="<?= $action_modal ?>">
                    <?= $action_title ?>
                </button>
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
    </div>
</div>
