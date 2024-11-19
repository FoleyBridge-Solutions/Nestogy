<?php
require_once "/var/www/nestogy/includes/inc_portal.php";

$asset_id = $_GET['id'];

$asset_sql = "SELECT * FROM assets WHERE asset_id = $asset_id";
$asset = mysqli_query($mysqli, $asset_sql);
$asset = mysqli_fetch_assoc($asset);

$rmm_asset = getAssetFromRMM($asset['asset_rmm_id']);

?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3>Asset Details</h3>
            </div>
            <div class="card-body">
                <h4>Asset Information</h4>
                <table class="table table-striped">
                    <tr>
                        <th>Asset ID</th>
                        <td><?php echo htmlspecialchars($asset['asset_id']); ?></td>
                        <th>Asset Type</th>
                        <td><?php echo htmlspecialchars($asset['asset_type']); ?></td>
                    </tr>
                    <tr>
                        <th>Name</th>
                        <td><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                        <th>Description</th>
                        <td><?php echo htmlspecialchars($asset['asset_description']); ?></td>
                    </tr>
                    <tr>
                        <th>Make</th>
                        <td><?php echo htmlspecialchars($asset['asset_make']); ?></td>
                        <th>Model</th>
                        <td><?php echo htmlspecialchars($asset['asset_model']); ?></td>
                    </tr>
                    <tr>
                        <th>Serial</th>
                        <td><?php echo htmlspecialchars($asset['asset_serial']); ?></td>
                        <th>OS</th>
                        <td><?php echo htmlspecialchars($asset['asset_os']); ?></td>
                    </tr>
                    <tr>
                        <th>IP Address</th>
                        <td><?php echo htmlspecialchars($asset['asset_ip']); ?></td>
                        <th>NAT IP</th>
                        <td><?php echo htmlspecialchars($asset['asset_nat_ip']); ?></td>
                    </tr>
                    <tr>
                        <th>MAC Address</th>
                        <td><?php echo htmlspecialchars($asset['asset_mac']); ?></td>
                        <th>Status</th>
                        <td><?php echo htmlspecialchars($asset['asset_status']); ?></td>
                    </tr>
                    <tr>
                        <th>RMM URI</th>
                        <td colspan="3"><a href="<?php echo htmlspecialchars($asset['asset_uri']); ?>" target="_blank"><?php echo htmlspecialchars($asset['asset_uri']); ?></a></td>
                    </tr>
                </table>

                <?php if (!empty($rmm_asset)): ?>
                <h4 class="mt-4">RMM Asset Information</h4>
                <pre><?php print_r($rmm_asset); ?></pre>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
require_once "portal_footer.php";
?>