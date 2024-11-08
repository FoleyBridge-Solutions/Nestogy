<?php require_once "/var/www/portal.twe.tech/includes/inc_all_modal.php";

$clients_sql = "SELECT * FROM clients WHERE client_archived_at IS NULL ORDER BY client_name ASC";
$clients = mysqli_query($mysqli, $clients_sql);
$clients = mysqli_fetch_all($clients, MYSQLI_ASSOC);
?>

<div class="modal fade" id="addTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-fw fa-life-ring mr-2"></i>New SOP</h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="/post.php" method="post" autocomplete="off">
                <div class="modal-body bg-white">
                    <input type="hidden" name="sop_add_modal_submit" value="1">
                    <div class="form-group">
                        <label>Title <strong class="text-danger">*</strong></label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Description <strong class="text-danger">*</strong></label>
                        <input type="text" class="form-control" name="description" required>
                    </div>
                    <div class="form-group">
                        <?php if (!isset($client_id)) { ?>
                            <label>Clients</label>
                            <br>
                            <select class="form-control select2" name="client_id[]" multiple>
                            <?php foreach ($clients as $client) { ?>
                                <option value="<?php echo $client['client_id']; ?>"><?php echo $client['client_name']; ?></option>
                            <?php } ?>
                        </select>
                        <?php } ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="sop_add_modal_submit" name="sop_add_modal_submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>