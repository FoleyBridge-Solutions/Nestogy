<?php
/*
 * Client Portal
 * Subscriptions for PTC / billing contacts
 */



require_once "/var/www/nestogy/includes/inc_portal.php";

if ($contact_primary == 0 && !$contact_is_billing_contact) {
    header("Location: portal_post.php?logout");
    exit();
}

$subscriptions_sql = mysqli_query($mysqli, "SELECT * FROM subscriptions
LEFT JOIN products ON subscriptions.subscription_product_id = products.product_id
WHERE subscription_client_id = $client_id");

$monthly_subscriptions = [];
$yearly_subscriptions = [];
while ($subscription = mysqli_fetch_assoc($subscriptions_sql)) {
    if ($subscription['subscription_term'] == 'monthly') {
        $monthly_subscriptions[] = $subscription;
    } else {
        $yearly_subscriptions[] = $subscription;
    }
}

$monthly_total = 0;
$yearly_total = 0;
?>

<div class="row">
    <div class="col-md-10">
        <!-- Monthly Subscriptions -->
        <h3>Monthly Subscriptions</h3>
        <table id="responsive-monthly" class="responsive table tabled-bordered border border-dark">
            <thead class="thead-dark">
                <tr>
                    <th>Product</th>
                    <th>Term</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthly_subscriptions as &$subscription) { 
                    $tax_rate = $subscription['tax_percent'];
                    $product_subtotal = $subscription['product_price'] * $subscription['subscription_product_quantity'];
                    $client_taxable = $subscription['client_taxable'] == 1 ? true : false;
                    $product_tax = $product_subtotal * ($tax_rate / 100);
                    if ($client_taxable) {
                        $product_total = $product_subtotal + $product_tax;
                    } else {
                        $product_total = $product_subtotal;
                    }
                    $monthly_total += $product_total;
                ?>
                    <tr>
                        <td><?= $subscription['product_name'] ?></td>
                        <td><?= ucfirst($subscription['subscription_term']) ?></td>
                        <td><?= $subscription['subscription_product_quantity'] ?></td>
                        <td><?= numfmt_format_currency(new NumberFormatter('en_US', NumberFormatter::CURRENCY), $product_total, "USD") ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <div class="row mb-4">
            <div class="col-md-10">
                <h4>Monthly Total: <?= numfmt_format_currency(new NumberFormatter('en_US', NumberFormatter::CURRENCY), $monthly_total, "USD") ?></h4>
            </div>
        </div>

        <!-- Yearly Subscriptions -->
        <h3>Yearly Subscriptions</h3>
        <table id="responsive-yearly" class="responsive table tabled-bordered border border-dark">
            <thead class="thead-dark">
                <tr>
                    <th>Product</th>
                    <th>Term</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($yearly_subscriptions as &$subscription) { 
                    $tax_rate = $subscription['tax_percent'];
                    $product_subtotal = $subscription['product_price'] * $subscription['subscription_product_quantity'];
                    $client_taxable = $subscription['client_taxable'] == 1 ? true : false;
                    $product_tax = $product_subtotal * ($tax_rate / 100);
                    if ($client_taxable) {
                        $product_total = $product_subtotal + $product_tax;
                    } else {
                        $product_total = $product_subtotal;
                    }
                    $yearly_total += $product_total;
                ?>
                    <tr>
                        <td><?= $subscription['product_name'] ?></td>
                        <td><?= ucfirst($subscription['subscription_term']) ?></td>
                        <td><?= $subscription['subscription_product_quantity'] ?></td>
                        <td><?= numfmt_format_currency(new NumberFormatter('en_US', NumberFormatter::CURRENCY), $product_total, "USD") ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <div class="row">
            <div class="col-md-10">
                <h4>Yearly Total: <?= numfmt_format_currency(new NumberFormatter('en_US', NumberFormatter::CURRENCY), $yearly_total, "USD") ?></h4>
            </div>
        </div>

        <!-- Combined Total -->
        <div class="row mt-4">
            <div class="col-md-10">
                <h3>Average Monthly Total: <?= numfmt_format_currency(new NumberFormatter('en_US', NumberFormatter::CURRENCY), ($monthly_total + ($yearly_total / 12)), "USD") ?></h3>
            </div>
        </div>
    </div>

<?php
require_once "portal_footer.php";
