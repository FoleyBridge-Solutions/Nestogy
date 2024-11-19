
<h4>Sales Overview</h4>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Total Orders</h5>
                <h3><?= number_format($dashboards['sales']['total_orders']) ?></h3>
                <p class="<?= $dashboards['sales']['orders_trend'] >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $dashboards['sales']['orders_trend'] ?>% from last month
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Average Order Value</h5>
                <h3>$<?= number_format($dashboards['sales']['avg_order_value'], 2) ?></h3>
                <p class="<?= $dashboards['sales']['aov_trend'] >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $dashboards['sales']['aov_trend'] ?>% from last month
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Conversion Rate</h5>
                <h3><?= number_format($dashboards['sales']['conversion_rate'], 1) ?>%</h3>
                <p class="<?= $dashboards['sales']['conversion_trend'] >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $dashboards['sales']['conversion_trend'] ?>% from last month
                </p>
            </div>
        </div>
    </div>
</div>
