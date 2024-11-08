<?php
require_once "/var/www/portal.twe.tech/includes/inc_all_modal.php";

$payment_reference = $_GET['payment_reference'];
//get all invoices for this payment reference

$sql = "SELECT * FROM payments LEFT JOIN invoices ON payments.payment_invoice_id = invoices.invoice_id WHERE payments.payment_reference = '$payment_reference'";
$result = mysqli_query($mysqli, $sql);
$invoices = mysqli_fetch_all($result, MYSQLI_ASSOC);

?>
<div class="modal-dialog">
    <div class="modal-content bg-dark">
        <div class="modal-header">
            <h5 class="modal-title">
                <i class="fa fa-file-invoice"></i>
                Invoices for Payment Reference: <?php echo $payment_reference; ?>
            </h5>
        </div>
        <div class="modal-body">
            <div class="row">
                <?php foreach ($invoices as $invoice) { ?>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><a href="/public/?page=invoice&invoice_id=<?php echo $invoice['invoice_id']; ?>"><?php echo $invoice['invoice_number']; ?></a></h5>
                                <p class="card-text">Status: <?php echo $invoice['invoice_status']; ?></p>
                                <p class="card-text">Amount: <?php echo $invoice['payment_amount']; ?></p>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>