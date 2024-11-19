<div class="card">
    <div class="card-header text-bold d-flex justify-content-between align-items-center">
        <div>
            <i class="fa fa-cog mr-2"></i>Tickets
        </div>
        <div class="card-tools">
            <a class="btn btn-tool loadModalContentBtn" 
               href="#" 
               data-bs-toggle="modal" 
               data-bs-target="#dynamicModal" 
               data-modal-file="invoice_add_ticket_modal.php?invoice_id=<?= $invoice_id ?>">
                <i class="fas fa-plus"></i>
            </a>
        </div>
    </div>
    <hr class="my-0" />
    <div class="card-body">
        <?php if (isset($tickets)): ?>
            <?php foreach ($tickets as $ticket): ?>
                <div class="d-flex justify-content-between">
                    <div>
                        <a href="/old_pages/ticket.php?ticket_id=<?= $ticket['ticket_id'] ?>">
                            <?= nullable_htmlentities($ticket['ticket_subject']) ?>
                        </a>
                        <p class="mb-0">
                            <?= nullable_htmlentities($ticket['ticket_status']) ?> | 
                            <?= nullable_htmlentities($ticket['ticket_priority']) ?> | 
                            <?= intval($ticket['ticket_assigned_to']) ?> | 
                            <?= floatval($ticket['total_time_worked']) ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div> 