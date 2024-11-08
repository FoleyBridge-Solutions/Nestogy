<!-- src/view/client.php -->
<?php
// src/View/client.php

$activities_count = 0;

// Helper function to determine icon and color based on activity type
function getActivityStyle($activity) {
    $type = strtolower($activity['log_type'] ?? 'default');
    switch ($type) {
        case 'client login':
            return ['icon' => 'bx-log-in-circle', 'color' => 'success'];
        case 'contact':
            return ['icon' => 'bx-user', 'color' => 'info'];
        case 'quote':
            return ['icon' => 'bx-file', 'color' => 'warning'];
        default:
            return ['icon' => 'bx-history', 'color' => 'secondary'];
    }
}

// Helper function to get the initial letter for the avatar
function getInitial($description) {
    $words = explode(' ', $description);
    return strtoupper(substr($words[0], 0, 1));
}
?>

<div class="card">
    <div class="card-body">
        <h2 class="mb-4">Recent Activities</h2>
        <ul class="timeline timeline-center">
            <!-- Now indicator -->
            <li class="timeline-item">
                <span class="timeline-indicator timeline-indicator-primary">
                    <i class="bx bx-time"></i>
                </span>
                <div class="timeline-event">
                    <h6 class="mb-0">Now</h6>
                </div>
            </li>

            <?php if (empty($client['recent_activities'])): ?>
                <li>No Recent Activities</li>
            <?php else: ?>
                <?php foreach ($client['recent_activities'] as $activity):
                    $activities_count++;
                    $style = getActivityStyle($activity);
                    $initial = getInitial($activity['log_description']);
                ?>
                <li class="timeline-item mb-4">
                    <span class="timeline-indicator timeline-indicator-<?php echo $style['color']; ?>">
                        <i class="bx <?php echo $style['icon']; ?>"></i>
                    </span>
                    <div class="timeline-event">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mb-0"><?php echo $activity['log_type']; ?></h6>
                            <span class="badge bg-label-<?php echo $style['color']; ?>"><?php echo $activity['log_type']; ?></span>
                        </div>
                        <p class="mb-2"><?php echo $activity['log_description']; ?></p>
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-xs me-2">
                                <span class="avatar-initial rounded-circle bg-label-<?php echo $style['color']; ?>"><?php echo $initial; ?></span>
                            </div>
                            <small><?php echo $activity['log_created_at']; ?></small>
                        </div>
                    </div>
                    <div class="timeline-event-time"><?php echo $activity['log_date']; ?></div>
                </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
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
