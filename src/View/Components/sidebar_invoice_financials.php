<?php
// Amount Due
$amountDue = $invoice_balance;

// Amount Paid (if applicable)
$amountPaid = $amount_paid;

// Margin
$margin = $total_cost != 0 ? $profit / $subtotal : 0;
$marginPercentage = $margin * 100;

// Deposit (if applicable)
$deposit = $invoice_deposit_amount;
?>

<!-- Amount Due -->
<div class="d-flex justify-content-between">
    <div class="d-flex align-items-center">
        <i class="bx bx-dollar me-2"></i>
        <span class="fw-medium">Amount Due:</span>
    </div>
    <span class="fw-medium">
        <?= numfmt_format_currency($currency_format, $amountDue, $invoice_currency_code) ?>
    </span>
</div>

<!-- Amount Paid (if applicable) -->
<?php if ($amountPaid > 0): ?>
    <div class="d-flex justify-content-between mt-3">
        <div class="d-flex align-items-center">
            <i class="bx bx-credit-card me-2"></i>
            <span class="fw-medium">Amount Paid:</span>
        </div>
        <span class="fw-medium">
            <?= numfmt_format_currency($currency_format, $amountPaid, $invoice_currency_code) ?>
        </span>
    </div>
<?php endif; ?>

<!-- Margin -->
<div class="d-flex justify-content-between mt-3">
    <div class="d-flex align-items-center">
        <i class="bx bx-dollar me-2"></i>
        <span class="fw-medium">Margin:</span>
    </div>
    <span class="fw-medium">
        <?= number_format($marginPercentage, 1) ?>%
    </span>
</div>

<!-- Deposit (if applicable) -->
<?php if ($deposit > 0): ?>
    <div class="d-flex justify-content-between mt-3">
        <div class="d-flex align-items-center">
            <i class="bx bx-dollar me-2"></i>
            <span class="fw-medium">Deposit:</span>
        </div>
        <span class="fw-medium">
            <?= numfmt_format_currency($currency_format, $deposit, $invoice_currency_code) ?>
        </span>
    </div>
<?php endif; ?> 