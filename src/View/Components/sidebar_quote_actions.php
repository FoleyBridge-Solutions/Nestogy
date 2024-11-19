<div class="d-grid d-flex my-3">
    <a href="/post.php?add_quote_to_invoice&quote_id=<?= $invoice_id ?>" 
       class="btn btn-label-success me-3 w-100 confirm-link">
        <i class="bx bx-check me-1"></i>Approve Quote
    </a>
    <a href="/post.php?email_quote=<?= $invoice_id ?>" 
       class="btn btn-label-primary me-3 w-100 confirm-link">
        <i class="bx bx-paper-plane me-1"></i>Send Quote
    </a>
</div> 