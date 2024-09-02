<!-- src/view/client.php -->
<?php
// src/View/client.php

$datatable_settings = "";
$ticket_count = 0;
$activities_count = 0;

?>

<div class="card">
    <div class="card-body">
        <h2> Technical Overview </h2>
        <!-- Cards Container -->
        <div class="d-flex flex-wrap">
            <!-- Tickets Card -->
            <div class="p-2" style="flex: 1 1 33%;">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bx bx-message-square-detail"></i>
                            Tickets
                        </h5>
                    </div>
                    <div class="card-body p-2">
                        <?php foreach ($client['tickets'] as $ticket) : ?>
                            <div class="row p-3">
                                <?php
                                $ticket_details_without_html = strip_tags($ticket['ticket_details']);
                                $ticket_status = $ticket['ticket_status'];
                                $ticket_status_class = '';
                                $ticket_count ++;
                                if ($ticket_status == 'open') {
                                    $ticket_status_class = 'text-success';
                                } else if ($ticket_status == 'closed') {
                                    $ticket_status_class = 'text-danger';
                                }
                                ?>
                                <div class="ticket col-6">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?php echo $ticket['ticket_subject']; ?></h6>
                                        <span class="badge bg-<?php echo $ticket_status_class; ?>"><?php echo $ticket_status; ?></span>
                                    </div>
                                    <p class="text-muted text-truncate"><?php echo truncate($ticket_details_without_html, 50); ?></p>
                                </div>
                                <div class="col-6 text-end">
                                    <div class="btn-group">
                                        <a href="/public/?page=ticket&ticket_id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="bx bx-show"></i>
                                            View
                                        </a>
                                        <a href="/public/?page=ticket&ticket_id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-sm btn-secondary">
                                            <i class="bx bx-pencil"></i>
                                            Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer">
                        <a href="/public/?page=tickets&client_id=<?php echo $client['client']['client_id']; ?>" class="btn btn-sm btn-primary">
                            <i class="bx bx-plus"></i>
                            Show More
                        </a>
                    </div>
                </div>
            </div>
            <!-- Recent Activities Card -->
            <div class="p-2" style="flex: 1 1 33%;">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="card-title">
                            Recent Activities
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <?php
                                // Recent Activities
                                if (empty($client['recent_activities'])) {
                                    echo 'No Recent Activities';
                                } else {
                                    foreach ($client['recent_activities'] as $activity) {
                                        $activities_count++;
                                        if ($activities_count > $ticket_count && $ticket_count != 0) {
                                            continue;
                                        }
                                    ?>
                                    <div class="row">
                                        <div class="col">
                                            <h6><?php echo $activity['log_description']; ?></h6>
                                            <p class="text-muted"><?php echo date('F j, Y @ g:i A', strtotime($activity['log_created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <?php
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-sm btn-primary loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="activities.php?client_id=<?php echo $client['client_id']; ?>">
                            <i class="bx bx-show"></i>
                            Show More
                        </button>
                    </div>
                </div>
            </div>
            <!-- Upcoming Expirations Card -->
            <div class="p-2" style="flex: 1 1 33%;">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bx bx-time"></i>
                            Upcoming Expirations
                        </h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="row">
                            <div class="col">
                                <?php
                                // Domains
                                if (empty($client['domains'])) {
                                    echo 'No Domains Expiring Soon';
                                } else {
                                ?>
                                    <table class="table table-borderless table-sm">
                                        <tbody>
                                            <thead>
                                                <tr>
                                                    <th>Domain</th>
                                                    <th>Expiration Date</th>
                                                </tr>
                                            </thead>
                                        <tbody>
                                            <?php foreach ($client['domains'] as $domain) : ?>
                                                <tr>
                                                    <td><?php echo $domain['domain_name']; ?></td>
                                                    <td><?php echo $domain['domain_expiration_date']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="col">
                            <?php
                            // Asset Warranties
                            if (empty($client['assets'])) {
                                echo 'No Assets with Warranties Expiring Soon';
                            } else {
                            ?>
                                <table class="table table-borderless table-sm">
                                    <tbody>
                                        <thead>
                                            <tr>
                                                <th>Asset</th>
                                                <th>Warranty Expiration Date</th>
                                            </tr>
                                        </thead>
                                    <tbody>
                                        <?php foreach ($client['assets'] as $asset) : ?>
                                            <tr>
                                                <td><?php echo $asset['asset_name']; ?></td>
                                                <td><?php echo $asset['asset_warranty_expiration_date']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateClientNotes(client_id) {
        var notes = document.getElementById("clientNotes").value;

        // Send a POST request to ajax.php as ajax.php with data client_set_notes=true, client_id=NUM, notes=NOTES
        jQuery.post(
            "/ajax/ajax.php", {
                client_set_notes: 'TRUE',
                client_id: client_id,
                notes: notes
            }
        )
    }
</script>