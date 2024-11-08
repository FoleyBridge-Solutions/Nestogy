<?php
require_once "/var/www/portal.twe.tech/includes/inc_portal.php";

$client_id = $_SESSION['client_id'];

?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3>Assets</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="datatables-basic table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Asset Name</th>
                                <th>Asset Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch devices from the database
                            $assets_sql = "SELECT * FROM assets WHERE asset_client_id = $client_id ORDER BY asset_type ASC, asset_name ASC";
                            $assets = mysqli_query($mysqli, $assets_sql);
                            foreach ($assets as $asset) {
                                ?>
                                <tr>
                                    <td><?php echo $asset['asset_name']; ?></td>
                                    <td><?php echo $asset['asset_type']; ?></td>
                                    <td>
                                        <a href="asset_details.php?id=<?php echo $asset['asset_id']; ?>" class="btn btn-primary">View Details</a>
                                        <a href="asset_edit.php?id=<?php echo $asset['asset_id']; ?>" class="btn btn-secondary">Edit</a>
                                        <a href="asset_delete.php?id=<?php echo $asset['asset_id']; ?>" class="btn btn-danger">Delete</a>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
require_once "portal_footer.php";
?>
                            