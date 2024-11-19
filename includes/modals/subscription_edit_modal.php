<?php
require "../../bootstrap.php";

use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\Database;

$config = require '/var/www/portal.twe.tech/config/portal.twe.tech/config.php';
$database = new Database($config['db']);
$pdo = $database->getConnection();

$accounting = new Accounting($pdo);

$subscription_id = $_GET['subscription_id'];
$subscription = $accounting->getSubscription($subscription_id);
$client_taxable = $subscription['client_taxable'] == 1 ? "true" : "false";

$tax_rate = $subscription['tax_percent'];
?>

<div class="modal-header">
    <h5 class="modal-title">Edit Subscription Product</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <input type="hidden" name="subscription_id" value="<?= $subscription['subscription_id'] ?>">
    <div class="form-group">
        <label for="subscription_product_id">Product</label>
        <select class="form-control" id="subscription_product_id" name="subscription_product_id" disabled>
            <option value="<?= $subscription['product_id'] ?>"><?= $subscription['product_name'] ?></option>
        </select>
    </div>
    <div class="form-group">
        <label for="subscription_product_term">Term</label>
        <select class="form-control" id="subscription_product_term" name="subscription_product_term">
            <option value="monthly" <?= $subscription['subscription_term'] == 'monthly' ? 'selected' : '' ?>>Monthly</option>
            <option value="yearly" <?= $subscription['subscription_term'] == 'yearly' ? 'selected' : '' ?>>Yearly</option>
        </select>
    </div>
    <div class="form-group">
        <label for="subscription_product_quantity">Quantity</label>
        <input class="form-control" id="subscription_product_quantity" name="subscription_product_quantity" value="<?= $subscription['subscription_product_quantity'] ?>">
    </div>
    <div class="form-group">
        <label for="subscription_product_price" disabled>Price</label>
        <input class="form-control" id="subscription_product_price" name="subscription_product_price" value="<?= $subscription['product_price'] ?>" disabled>
    </div>
    <div class="form-group">
        <label for="subscription_product_total" disabled>Total</label>
        <input class="form-control" id="subscription_product_total" name="subscription_product_total">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-primary" name="update_subscription">Save</button>
</div>
<script>
    $(document).ready(function() {
        $('#subscription_product_quantity').on('change', function() {
            var quantity = $(this).val();
            var price = $('#subscription_product_price').val();
            var total = quantity * price;
            if (<?= $client_taxable ?>) {
                var tax = total * <?= $tax_rate/100 ?>;
                var total_with_tax = total + tax;
                $('#subscription_product_total').val(total_with_tax.toFixed(2));
            } else {
                $('#subscription_product_total').val(total);
            }
        });
    });
</script>
        
    