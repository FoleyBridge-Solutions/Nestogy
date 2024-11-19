<?php
// Determine the document type and URLs
$documentType = strtolower($wording); // 'invoice' or 'quote'
$viewUrl = match($documentType) {
    'invoice' => "/portal/guest_view_invoice.php?invoice_id=$invoice_id&url_key=$invoice_url_key",
    'quote' => "/portal/guest_view_quote.php?quote_id=$invoice_id&url_key=$quote_url_key",
};
$deleteUrl = "/post.php?delete_{$documentType}=$invoice_id";
?>

<!-- Invoice Actions -->
<div class="col-lg-3 col-md-12 order-lg-2 order-md-1 order-1">
    <div class="card mb-4">
        <div class="card-body">
            <!-- Common View/Delete Actions -->
            <div class="d-grid d-flex my-3">
                <a target="_blank" href="<?= $viewUrl ?>" class="btn btn-label-primary me-3 w-100">
                    <i class="bx bx-show me-1"></i>View
                </a>
                <a href="<?= $deleteUrl ?>" class="btn btn-label-danger me-3 w-100 confirm-link">
                    <i class="bx bx-trash me-1"></i>Delete
                </a>
            </div>

            <!-- Document-specific actions -->
            <?php include __DIR__ . "/sidebar_{$documentType}_actions.php"; ?>
        </div>
    </div>

    <!-- Tickets section (invoices only) -->
    <?php if ($documentType === 'invoice') include __DIR__ . '/sidebar_invoice_tickets.php'; ?>
</div> 