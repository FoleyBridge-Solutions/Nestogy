<?php include_once '/var/www/portal.twe.tech/bootstrap.php';

use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\Model\Client;


require_once "/var/www/portal.twe.tech/includes/inc_all_modal.php";


$accounting = new Accounting($pdo);
$client = new Client($pdo);


$transaction_id = $_GET['transaction_id'];
$sql_transaction = mysqli_query($mysqli, "SELECT * FROM bank_transactions WHERE transaction_id = '$transaction_id'");
$row = mysqli_fetch_array($sql_transaction);
$transaction_amount = $row['amount'] * -1;
$transaction_name = $row['name'];

if ($transaction_amount < 0) {
    $type = 'expense';
    $expenses = $accounting->matchExpense($transaction_id);
    if (count($expenses) == 0) {
        $force_create = true;
    }
} else {
    $type = 'income';
    $incomes = $accounting->matchIncome($transaction_id);
    if (count($incomes) == 0) {
        $force_create = true;
    }
}
?>

<div class="modal" id="reconcileTransactionModal<?= $transaction_id; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title">
                    <img src="<?= $row['icon_url']; ?>" class="mr-2" style="width: 40px; height: 40px;">
                    Reconcile <?= ucfirst($type) ?>
                </h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body bg-white">
                <form action="/post.php" method="post">
                    <!-- Nav tabs -->
                    <ul class="nav nav-pills mb-3" id="reconcileTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="transaction-info-tab" data-bs-toggle="pill"
                                href="#transaction-info" role="tab">Transaction Info</a>
                        </li>
                        <?php if (!$force_create) : ?>
                            <li class="nav-item">
                                <a class="nav-link" id="link-existing-tab" data-bs-toggle="pill" href="#link-existing"
                                    role="tab"><?php if ($type == 'expense') {
                                                    echo 'Link to Existing Expense';
                                                } else {
                                                    echo 'Link to Existing Income';
                                                } ?></a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" id="create-new-tab" data-bs-toggle="pill" href="#create-new"
                                role="tab"><?php if ($type == 'expense') {
                                                echo 'Create New Expense';
                                            } else {
                                                echo 'Record new Payment';
                                            } ?></a>
                        </li>
                        <?php if ($type == 'expense') : ?>
                            <li class="nav-item">
                                <a class="nav-link" id="owners-draw-tab" data-bs-toggle="pill" href="#owners-draw"
                                    role="tab">Mark as Owner's Draw</a>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content" id="reconcileTabContent">
                        <!-- Transaction Info Pane -->
                        <div class="tab-pane fade show active" id="transaction-info" role="tabpanel">
                            <div class="row">
                                <div class="col-md-12">
                                    <h6>Transaction Information</h6>
                                    <ul>
                                        <li>Date: <?= date('F j, Y', strtotime($row['date'])); ?></li>
                                        <li>Amount:
                                            <?= numfmt_format_currency($currency_format, $transaction_amount, 'USD'); ?>
                                        </li>
                                        <li>Description: <?= $row['name']; ?></li>
                                        <li>Category: <?= $row['category']; ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Link to Existing Expense/Income Pane -->
                        <div class="tab-pane fade" id="link-existing" role="tabpanel">
                            <div class="row">
                                <div class="col-md-12">
                                    <input type="hidden" name="bank_transaction_id" value="<?= $transaction_id ?>">
                                    <div class="form-group">
                                        <label for="payment_id">Payment</label>
                                        <select class="form-control select2" id="payment_id" name="payment_id">
                                            <?php foreach ($incomes as $income) : ?>
                                                <option value="<?= $income['payment_id'] ?>">
                                                    <?= $income['client_name'] . ' [ ' . numfmt_format_currency($currency_format, $transaction_amount, 'USD') . ' - ' . $income['payment_date'] . ' ]' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary"
                                        name="link_payment_to_transaction">Link</button>
                                </div>
                            </div>
                        </div>
                        <!-- Create New Expense/Income Pane -->
                        <div class="tab-pane fade" id="create-new" role="tabpanel">
                            <?php if ($type == 'expense') {
                                $clients = $client->getClients();
                                $expense_categories = $accounting->getExpenseCategories();
                            ?>

                                <div class="form-row">
                                    <div class="form-group col-md">
                                        <label>Date <strong class="text-danger">*</strong></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-fw fa-calendar"></i></span>
                                            </div>
                                            <input type="date" class="form-control" name="date" max="2999-12-31"  <?php if (isset($row['date'])) {
                                                                                                                                echo 'value="' . $row['date'] . '"';
                                                                                                                            } ?>>
                                        </div>
                                    </div>

                                    <div class="form-group col-md">
                                        <label>Amount <strong class="text-danger">*</strong></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i
                                                        class="fa fa-fw fa-dollar-sign"></i></span>
                                            </div>
                                            <input type="text" class="form-control" inputmode="numeric"
                                                pattern="[0-9]*\.?[0-9]{0,2}" name="amount" placeholder="0.00"  <?php if (isset($row['amount'])) {
                                                                                                                            echo 'value="' . $row['amount'] . '"';
                                                                                                                        } ?>>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md">
                                        <label>Vendor <strong class="text-danger">*</strong></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-fw fa-building"></i></span>
                                            </div>
                                            <select class="form-control select2" name="vendor" >
                                                <option value="">- Vendor -</option>
                                                <?php

                                                $sql = mysqli_query($mysqli, "SELECT vendor_id, vendor_name FROM vendors WHERE vendor_client_id = 0 AND vendor_template = 0 AND vendor_archived_at IS NULL ORDER BY vendor_name ASC");
                                                while ($row = mysqli_fetch_array($sql)) {
                                                    $vendor_id = intval($row['vendor_id']);
                                                    $vendor_name = nullable_htmlentities($row['vendor_name']);
                                                ?>
                                                    <option value="<?= $vendor_id; ?>"><?= $vendor_name; ?></option>

                                                <?php
                                                }
                                                ?>
                                            </select>
                                            <div class="input-group-append">
                                                <a class="btn btn-light" href="vendors.php" target="_blank">
                                                    <i class="fas fa-fw fa-plus"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Description <strong class="text-danger">*</strong></label>
                                    <input type="text" class="form-control" name="description"
                                    value="<?= $transaction_name ?>"
                                    required>
                                </div>

                                <div class="form-group">
                                    <label>Reference</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-fw fa-file-alt"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="reference"
                                            placeholder="Enter a reference">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md">
                                        <label>Product</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-fw fa-box"></i></span>
                                            </div>
                                            <select class="form-control select2" name="product">
                                                <option value="">- Product (Optional) -</option>
                                                <?php

                                                $sql = mysqli_query($mysqli, "SELECT product_id, product_name FROM products WHERE product_archived_at IS NULL ORDER BY product_name ASC");
                                                while ($row = mysqli_fetch_array($sql)) {
                                                    $product_id = intval($row['product_id']);
                                                    $product_name = nullable_htmlentities($row['product_name']);
                                                ?>
                                                    <option value="<?= $product_id; ?>"><?= $product_name; ?></option>

                                                <?php
                                                }
                                                ?>
                                            </select>
                                            <div class="input-group-append">
                                                <a class="btn btn-light" href="products.php" target="_blank"><i
                                                        class="fas fa-fw fa-plus"></i></a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group col-md">
                                        <label>Quantity</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-fw fa-calculator"></i></span>
                                            </div>
                                            <input type="text" class="form-control" inputmode="numeric"
                                                pattern="[0-9]*\.?[0-9]{0,2}" name="product_quantity" placeholder="0.00">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md">
                                        <label>Category <strong class="text-danger">*</strong></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-fw fa-list"></i></span>
                                            </div>
                                            <select class="form-control select2" name="category" >
                                                <option value="">- Category -</option>
                                                <?php

                                                $sql = mysqli_query($mysqli, "SELECT category_id, category_name FROM categories WHERE category_type = 'Expense' AND category_archived_at IS NULL ORDER BY category_name ASC");
                                                while ($row = mysqli_fetch_array($sql)) {
                                                    $category_id = intval($row['category_id']);
                                                    $category_name = nullable_htmlentities($row['category_name']);
                                                ?>
                                                    <option value="<?= $category_id; ?>"><?= $category_name; ?></option>

                                                <?php
                                                }
                                                ?>
                                            </select>
                                            <div class="input-group-append">
                                                <a class="btn btn-light" href="admin_categories.php?category=Expense"
                                                    target="_blank"><i class="fas fa-fw fa-plus"></i></a>
                                            </div>
                                        </div>


                                    </div>

                                    <?php if (isset($_GET['client_id'])) { ?>
                                        <input type="hidden" name="client" value="<?= $client_id; ?>">
                                    <?php } else { ?>

                                        <div class="form-group col-md">
                                            <label>Client</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fa fa-fw fa-user"></i></span>
                                                </div>
                                                <select class="form-control select2" name="client">
                                                    <option value="0">- Client (Optional) -</option>
                                                    <?php

                                                    $sql = mysqli_query($mysqli, "SELECT client_id, client_name FROM clients ORDER BY client_name ASC");
                                                    while ($row = mysqli_fetch_array($sql)) {
                                                        $client_id = intval($row['client_id']);
                                                        $client_name = nullable_htmlentities($row['client_name']);
                                                    ?>
                                                        <option value="<?= $client_id; ?>"><?= $client_name; ?></option>

                                                    <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                    <?php } ?>

                                </div>

                                <div class="form-group col-md">
                                    <label>Receipt</label>
                                    <input type="file" class="form-control-file" name="file">
                                </div>

                                <button type="submit" name="add_expense" class="btn btn-label-primary text-bold"><i
                                        class="fa fa-fw fa-check mr-2"></i>Create</button>


                            <?php
                            } else {
                            ?>
                                <div class="row">
                                    <div class="col">
                                        <a href="https://portal.twe.tech/public/?page=make_payment&bank_transaction_id=<?= $transaction_id ?>"
                                            class="btn btn-primary">Create</a>
                                    <?php
                                } ?>
                                    </div>

                                    <!-- Mark as Owner's Draw Pane -->
                                    <div class="tab-pane fade" id="owners-draw" role="tabpanel">
                                        <a class="btn btn-warning" href="/post.php?create_owners_draw=true&transaction_id=<?= $transaction_id ?>" id="create-owners-draw">Create Owners Draw</a>
                                    </div>
                                </div>
                </form>
            </div>
        </div>
    </div>
</div>