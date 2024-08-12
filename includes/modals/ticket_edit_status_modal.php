<?php require_once "/var/www/portal.twe.tech/includes/inc_all_modal.php";

$ticket_id = intval($_GET['ticket_id']);

$sql = "SELECT * FROM tickets WHERE ticket_id = $ticket_id";
$result = mysqli_query($mysqli, $sql);
$row = mysqli_fetch_assoc($result);

$ticket_status = $row['ticket_status'];
$ticket_number = $row['ticket_number'];
$ticket_prefix = $row['ticket_prefix'];


?>

<div class="modal" id="editTicketBillableModal<?= $ticket_id; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-fw fa-user mr-2"></i>
                    Edit Status for <strong><?= "$ticket_prefix$ticket_number"; ?></strong>
                </h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="/post.php" method="post" autocomplete="off">
                <div class="modal-body bg-white">
                    <input type="hidden" name="ticket_id" value="<?= $ticket_id; ?>">
                    <input type="hidden" name="set_ticket_status" value="1">
                    <div class="form-group">
                        <label>Status</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-fw fa-money-bill"></i></span>
                                </div>
                                <select class="form-control" name="status">
                                    <option <?php if ($ticket_status == 1) { echo "selected"; } ?> value="1">New</option>
                                    <option <?php if ($ticket_status == 2) { echo "selected"; } ?> value="2">Open</option>
                                    <option <?php if ($ticket_status == 3) { echo "selected"; } ?> value="3">On Hold</option>
                                    <option <?php if ($ticket_status == 4) { echo "selected"; } ?> value="4">Resolved</option>
                                </select>
                            </div>

                    </div>

                </div>

                <div class="modal-footer bg-white">
                    <button type="submit" name="edit_ticket_billable" class="btn btn-label-primary text-bold"></i>Save</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"></i>Cancel</button>
                </div>

            </form>

        </div>
    </div>
</div>
