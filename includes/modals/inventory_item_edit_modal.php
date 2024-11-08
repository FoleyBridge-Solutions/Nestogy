<?php require_once "/var/www/portal.twe.tech/includes/inc_all_modal.php"; ?>

<?php
$inventory_id = isset($_GET['inventory_id']) ? intval($_GET['inventory_id']) : 0;

if (isset($_GET['inventory_id'])) {
    $inventory_sql = "SELECT * FROM inventory WHERE inventory_id = $inventory_id";
    $inventory_result = mysqli_query($mysqli, $inventory_sql);
    $inventory_row = mysqli_fetch_assoc($inventory_result);
    
    $inventory_location_id = $inventory_row['inventory_location_id'];
    $inventory_client_id = $inventory_row['inventory_client_id'];
    $inventory_notes = $inventory_row['inventory_notes'];
    $inventory_asset_tag = $inventory_row['inventory_asset_tag'];
    $inventory_locations = [];

    $inventory_locations_sql = mysqli_query($mysqli, "SELECT * FROM inventory_locations WHERE inventory_location_archived_at IS NULL");
    if (mysqli_num_rows($inventory_locations_sql) > 0) {
        while ($row = mysqli_fetch_array($inventory_locations_sql)) {
            $inventory_locations[] = $row;
        }
    }
} else {
    $inventory_location_id = 0;
}

?>

<div class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-fw fa-box mr-2"></i>Edit Inventory Item</h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body bg-white">

                <div class="form-group">
                    <label for="inventory_location_id">Location</label>
                    <select name="inventory_location_id" class="form-select">
                        <option value="0">Select Location</option>
                        <?php foreach ($inventory_locations as $inventory_location) { ?>
                            <option value="<?=$inventory_location['inventory_location_id']?>" <?php if ($inventory_location_id == $inventory_location['inventory_location_id']) { echo 'selected'; } ?>>
                                <?=$inventory_location['inventory_location_name']?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                        <label for="inventory_asset_tag">Asset Tag</label>
                    <div class="input-group">
                        <input disabled type="text" name="inventory_asset_tag" id="inventory_asset_tag" class="form-control" value="<?=$inventory_asset_tag?>">
                        <button type="button" class="btn btn-primary" onclick="copyAssetTag()">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">    
                <label for="inventory_notes">Notes</label>
                    <textarea name="inventory_notes" class="form-control"><?=$inventory_notes?></textarea> 
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyAssetTag() {
    var assetTagInput = document.getElementById('inventory_asset_tag');
    navigator.clipboard.writeText(assetTagInput.value)
        .then(() => {
            // Optional: Show a tooltip or alert that the copy was successful
            alert('Asset tag copied to clipboard!');
        })
        .catch(err => {
            console.error('Failed to copy text: ', err);
        });
}
</script>


