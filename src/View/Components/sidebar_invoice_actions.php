<?php if ($invoice_status == 'Draft'): ?>
    <!-- Send Actions -->
    <div class="d-grid d-flex my-3 w-100">
        <button class="btn btn-primary dropdown-toggle d-grid w-100 d-flex align-items-center justify-content-center text-nowrap" 
                type="button" data-bs-toggle="dropdown">
            <i class="fas fa-fw fa-paper-plane me-1"></i>Send
        </button>
        <div class="dropdown-menu">
            <a class="dropdown-item" href="/post.php?email_invoice=<?= $invoice_id ?>">
                <i class="fas fa-fw fa-paper-plane mr-2"></i>Send Email
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="/post.php?mark_invoice_sent=<?= $invoice_id ?>">
                <i class="fas fa-fw fa-check mr-2"></i>Mark Sent
            </a>
        </div>
    </div>

    <!-- Edit Button -->
    <div class="d-grid d-flex my-3 w-100">
        <button class="btn btn-primary loadModalContentBtn" 
                data-bs-toggle="modal" 
                data-bs-target="#dynamicModal" 
                data-modal-file="invoice_edit_modal.php?invoice_id=<?= $invoice_id ?>">
            <i class="fas fa-fw fa-edit me-1"></i>Edit
        </button>
    </div>
<?php endif; ?>

<!-- Payment Button -->
<div class="d-grid d-flex my-3">
    <button class="btn btn-primary d-grid w-100 loadModalContentBtn" 
            data-bs-toggle="modal" 
            data-bs-target="#dynamicModal" 
            data-modal-file="invoice_payment_add_modal.php?invoice_id=<?= $invoice_id ?>&balance=<?= $balance ?>">
        <span class="d-flex align-items-center justify-content-center text-nowrap">
            <i class="bx bx-dollar bx-xs me-1"></i>Add Payment
        </span>
    </button>
</div>

<!-- Cancel Button -->
<div class="d-grid d-flex my-3">
    <a href="/post.php?cancel_invoice=<?= $invoice_id ?>" class="btn btn-label-danger me-3 w-100 confirm-link">
        <i class="bx bx-x-circle me-1"></i>Cancel
    </a>
</div>

<!-- Send/Resend Email -->
<div class="d-grid d-flex my-3">
    <a href="/post.php?email_invoice=<?= $invoice_id ?>" class="btn btn-label-primary me-3 w-100 confirm-link">
        <i class="bx bx-refresh me-1"></i><?= $invoice_status == 'Sent' ? 'Resend' : 'Send' ?>
    </a>
</div>

<!-- Financial Information -->
<hr class="my-0" />
<div class="mt-3">
    <?php include __DIR__ . '/sidebar_invoice_financials.php'; ?>
</div> 