<div class="card">
    <div class="card-header">
        <h4>Recent Activities</h4>
    </div>
    <div class="card-body">
        <div class="activity-feed">
            <?php foreach ($recent_activities as $activity) { ?>
                <div class="activity-item">
                    <div class="activity-content">
                        <small class="text-muted"><?= date('M j, Y H:i', strtotime($activity['timestamp'])) ?></small>
                        <p class="mb-0"><?= $activity['description'] ?></p>
                    </div>
                </div>
            <?php } ?>
        </div>
        <?php if (empty($recent_activities)) { ?>
            <p class="text-center text-muted">No recent activities</p>
        <?php } ?>
    </div>
</div> 