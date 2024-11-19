
<h4>Support Overview</h4>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Open Tickets</h5>
                <h3><?= number_format($dashboards['support']['open_tickets']) ?></h3>
                <p class="<?= $dashboards['support']['tickets_trend'] <= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $dashboards['support']['tickets_trend'] ?>% from last month
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Average Response Time</h5>
                <h3><?= number_format($dashboards['support']['avg_response_time'], 1) ?>h</h3>
                <p class="<?= $dashboards['support']['response_trend'] <= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $dashboards['support']['response_trend'] ?>% from last month
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Customer Satisfaction</h5>
                <h3><?= number_format($dashboards['support']['satisfaction'], 1) ?>%</h3>
                <p class="<?= $dashboards['support']['satisfaction_trend'] >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $dashboards['support']['satisfaction_trend'] ?>% from last month
                </p>
            </div>
        </div>
    </div>
</div>
