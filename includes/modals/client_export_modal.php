<?php require_once "/var/www/portal.twe.tech/includes/inc_all_modal.php"; 
$client_id = intval($_GET['client_id']);
?>

<div class="modal" id="exportClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-fw fa-download mr-2"></i>Export Client to PDF</h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="/post.php" method="post" autocomplete="off">
                <div class="modal-body bg-white">

                    <?php require_once "/var/www/portal.twe.tech/includes/inc_export_warning.php";
 ?>
                <input type="hidden" name="client_id" value="<?= $client_id; ?>">
                <div class="form-group">
                    <label for="export_data">Export Data</label>
                    <select class="form-control select2" name="export_data[]" id="export_data" multiple>
                        <option value="all">All Data</option>
                        <option value="contacts">Contacts</option>
                        <option value="locations">Locations</option>
                        <option value="assets">Assets</option>
                        <option value="software">Software</option>
                        <option value="logins">Logins</option>
                        <option value="networks">Networks</option>
                        <option value="certificates">Certificates</option>
                        <option value="domains">Domains</option>
                        <option value="tickets">Tickets</option>
                        <option value="scheduled_tickets">Scheduled Tickets</option>
                        <option value="vendors">Vendors</option>
                        <option value="invoices">Invoices</option>
                        <option value="recurring">Recurring</option>
                        <option value="quotes">Quotes</option>
                        <option value="payments">Payments</option>
                        <option value="trips">Trips</option>
                        <option value="logs">Logs</option>
                    </select>
                </div>
                <div class="modal-footer bg-white">
                    <button type="submit" name="export_client_pdf" class="btn btn-label-primary text-bold"><i class="fas fa-fw fa-download mr-2"></i>Download PDF</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><i class="fas fa-times mr-2"></i>Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
