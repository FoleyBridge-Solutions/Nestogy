<?php
// src/View/simpleTable.php

$card_title = $card['title'];

$table_header_rows = $table['header_rows'];
$table_body_rows = $table['body_rows'];
//if action is not an array of arrays, make it an array of arrays
if (!is_array($action[0])) {
    $action = [$action];
}
if (!is_array($table['footer_row'])) {
    $table['footer_row'] = [$table['footer_row']];
}

?>
<?php if (isset($header_cards)) : ?>
    <div class="row">
    <?php foreach ($header_cards as $header_card) : ?>
        <?php //count how many cards there are and set the col-md-X class accordingly ?>
        <?php $card_count = count($header_cards); ?>
        <div class="col-md-<?= 12 / $card_count ?>">
            <div class="card mb-3">
                <div class="card-header header-elements">
                    <h5 class="card-header-title"><?= $header_card['title'] ?></h5>
                </div>
                <div class="card-body">
                    <?= $header_card['body'] ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


<div class="card">
    <div class="card-header header-elements">
        <h5 class="card-header-title"><?= $card_title ?></h5>
        <div class="card-header-elements ms-auto">
            <!-- Card Action -->
            <?php foreach ($action as $action_item) : ?>
                <?php if (isset($action_item['url'])) : ?>
                    <a href="<?= $action_item['url'] ?>" class="btn btn-primary">
                        <?= $action_item['title'] ?>
                    </a>
                <?php endif; ?>
                <?php if (isset($action_item['modal'])) : ?>
                    <button type="button" class="btn btn-primary loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="<?= $action_item['modal'] ?>">
                        <?= $action_item['title'] ?>
                    </button>
                <?php endif; ?>
            <?php endforeach; ?>

        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive card-datatable" id="<?= $table['id'] ?? 'simpleTable' ?>">
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
        <?php if (isset($table['footer_row'])) : ?>
            <?php foreach ($table['footer_row'] as $footer_row) : ?>
                <div class="card-footer">
                    <?= $footer_row ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($script)) : ?>
    <script>
        <?= $script ?>
    </script>
<?php endif; ?>