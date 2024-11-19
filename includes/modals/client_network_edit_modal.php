<?php require_once "/var/www/nestogy/includes/inc_all_modal.php"; ?>

<?php
$client_id = $_GET['client_id'];
$network_id = $_GET['network_id'];

$network = getNetwork($network_id);
$locations = getLocations($client_id);
?>

<div class="modal" id="editNetworkModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content bg-dark">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-fw fa-network-wired mr-2"></i>Edit network: <span class="text-bold" id="editNetworkHeader"><?= htmlspecialchars($network['network_name']); ?></span></h5>
        <button type="button" class="close text-white" data-bs-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <form action="/post.php" method="post" autocomplete="off">
        <input type="hidden" name="network_id" id="editNetworkId" value="<?= htmlspecialchars($network['network_id']); ?>">
        <input type="hidden" name="client_id" value="<?= htmlspecialchars($client_id); ?>">
        <div class="modal-body bg-white">

          <div class="form-group">
            <label>Name <strong class="text-danger">*</strong></label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa fa-fw fa-ethernet"></i></span>
              </div>
              <input type="text" class="form-control" id="editNetworkName" name="network_name" placeholder="Network name (VLAN, WAN, LAN2 etc)" value="<?= htmlspecialchars($network['network_name']); ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label>Description</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa fa-fw fa-angle-right"></i></span>
              </div>
              <input type="text" class="form-control" id="editNetworkDescription" name="network_description" placeholder="Short Description" value="<?= htmlspecialchars($network['network_description']); ?>">
            </div>
          </div>

          <div class="form-group">
            <label>vLAN</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa fa-fw fa-tag"></i></span>
              </div>
              <input type="text" class="form-control" inputmode="numeric" pattern="[0-9]*" id="editNetworkVlan" name="network_vlan" placeholder="ex. 20" value="<?= htmlspecialchars($network['network_vlan']); ?>">
            </div>
          </div>

          <div class="form-group">
            <label>Network <strong class="text-danger">*</strong></label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa fa-fw fa-network-wired"></i></span>
              </div>
                <input type="text" class="form-control" id="editNetworkCidr" name="network_cidr" placeholder="Network ex 192.168.1.0/24" value="<?= htmlspecialchars($network['network_cidr']); ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label>Gateway <strong class="text-danger">*</strong></label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa fa-fw fa-route"></i></span>
              </div>
              <input type="text" class="form-control" id="editNetworkGw" name="network_gateway" placeholder="ex 192.168.1.1" data-inputmask="'alias': 'ip'" data-mask value="<?= htmlspecialchars($network['network_gateway']); ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label>DHCP Range / IPs</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa fa-fw fa-server"></i></span>
              </div>
              <input type="text" class="form-control" id="editNetworkDhcp" name="network_dhcp_range" placeholder="ex 192.168.1.11-199" value="<?= htmlspecialchars($network['network_dhcp_range']); ?>">
            </div>
          </div>

          <div class="form-group">
            <label>Notes</label>
            <textarea class="form-control" rows="3" id="editNetworkNotes" name="network_notes" placeholder="Enter some notes"><?= htmlspecialchars($network['network_notes']); ?></textarea>
          </div>

          <div class="form-group">
            <label>Location</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa fa-fw fa-map-marker-alt"></i></span>
              </div>
                <select class="form-control select2" id="editNetworkLocation" name="network_location">
                <option value="">- Location -</option>
                <?php foreach ($locations as $location): ?>
                  <option value="<?= htmlspecialchars($location['location_id']); ?>"><?= htmlspecialchars($location['location_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

        </div>
        <div class="modal-footer bg-white">
          <button type="submit" name="edit_network" class="btn btn-label-primary text-bold">Save</button>
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
