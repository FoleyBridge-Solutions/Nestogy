<?php
require "../../bootstrap.php";

use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\Model\Client;

$accounting = new Accounting($pdo);
$clientModel = new Client($pdo);

$client_id = $_GET['client_id'];
$client = $clientModel->getClient($client_id);
$today = date('Y-m-d');

$subscriptions = $accounting->getSubscriptions($client_id);
$subscriptions_to_bill = [];

foreach ($subscriptions as $subscription) {
    $subscription_last_billed = $subscription['subscription_last_billed'] ?? "1900-01-01";
    $subscription_term = $subscription['subscription_term'];
    
    // Calculate next billing date based on term
    if ($subscription_term == 'monthly') {
        $subscription_next_billing = date('Y-m-d', strtotime($subscription_last_billed . ' + 1 month'));
    } elseif ($subscription_term == 'yearly') {
        $subscription_next_billing = date('Y-m-d', strtotime($subscription_last_billed . ' + 1 year'));
    } else {
        // Skip if term is neither monthly nor yearly
        continue;
    }

    // Check if it's time to bill
    if ($subscription_next_billing <= $today) {
        $subscriptions_to_bill[] = $subscription;
    }
}

$subscriptions_to_bill_grouped = [];
foreach ($subscriptions_to_bill as $subscription) {
    $client_id = $subscription['client_id'];
    $client_name = $subscription['client_name'];
    $subscription_term = $subscription['subscription_term'];
    $last_billed = $subscription['subscription_last_billed'] ?: 'Never';
    $subscriptions_to_bill_grouped[$client_name][$subscription_term][$last_billed][] = $subscription;
}

// Sort by client name
ksort($subscriptions_to_bill_grouped);
?>

<div class="modal-header">
    <h5 class="modal-title">Bill Subscriptions</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <?php foreach ($subscriptions_to_bill_grouped as $client_name => $terms): ?>
        <h3>Client:<?= htmlspecialchars($client_name) ?></a></h3>
        <?php foreach ($terms as $subscription_term => $last_billed_groups): ?>
            <h4><?= htmlspecialchars(ucfirst($subscription_term)) ?></h4>
            <?php foreach ($last_billed_groups as $last_billed => $subscription_data): ?>
                <div class="card p-2 mb-2">
                    <h5>Last Billed: <?= htmlspecialchars($last_billed) ?></h5>
                    <div class="card-body">
                    <?php 
                    $total_amount = 0;
                    foreach ($subscription_data as $subscription): 
                        $total_amount += $subscription['subscription_total'];
                    ?>
                        <h6><?= htmlspecialchars($subscription['product_name']) ?></h6>
                        <p>Quantity: <?= htmlspecialchars($subscription['subscription_product_quantity']) ?></p>
                        <p>Price: $<?= number_format($subscription['product_price'], 2) ?></p>
                        <p>Total: $<?= number_format($subscription['subscription_total'], 2) ?></p>
                    <?php endforeach ?>
                    </div>
                    <button type="button" class="btn btn-primary bill-subscription-btn" 
                            data-term="<?= htmlspecialchars($subscription_term) ?>" 
                            data-client-id="<?= $subscription_data[0]['client_id'] ?>"
                            data-last-billed="<?= htmlspecialchars($last_billed) ?>">
                        Bill <?= htmlspecialchars(ucfirst($subscription_term)) ?> Subscriptions 
                        (Last Billed: <?= htmlspecialchars($last_billed) ?>)
                        Total: $<?= number_format($total_amount, 2) ?>
                    </button>
                </div>
            <?php endforeach ?>
        <?php endforeach ?>
    <?php endforeach ?>
</div>

<script>
document.querySelectorAll('.bill-subscription-btn').forEach(button => {
    button.addEventListener('click', function() {
        const term = this.dataset.term;
        const clientId = this.dataset.clientId;
        const lastBilled = this.dataset.lastBilled;
        const buttonElement = this; // Store reference to the button
        // Add your AJAX call here to process the billing
        console.log(`Billing ${term} subscriptions for client ${clientId}, last billed on ${lastBilled}`);
        $.ajax({
            url: '/post.php',
            method: 'POST',
            data: {
                bill_subscription: true,
                term: term,
                client_id: clientId,
                last_billed: lastBilled
            },
            success: function(response) {
                // Use the stored reference to the button
                buttonElement.closest('.card').remove();
            }
        });
    });
});
</script>
