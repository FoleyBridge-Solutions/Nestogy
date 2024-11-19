<h4>Financial Overview</h4>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Revenue</h5>
                <canvas id="income-chart" width="400" height="400"></canvas>
                <h3>$<?= number_format($dashboards['financial']['revenue'], 2) ?></h3>
                <p class="<?= $dashboards['financial']['revenue_trend'] >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $dashboards['financial']['revenue_trend'] ?>% from last month
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Expenses</h5>
                <canvas id="expenses-chart" width="400" height="400"></canvas>
                <h3>$<?= number_format($dashboards['financial']['expenses'], 2) ?></h3>
                <p class="<?= $dashboards['financial']['expenses_trend'] <= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $dashboards['financial']['expenses_trend'] ?>% from last month
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Profit</h5>
                <div style="width: 300px; height: 300px;">
                    <canvas id="profit-trend-chart"></canvas>
                </div>
                <h3>$<?= number_format($dashboards['financial']['profit'], 2) ?></h3>
                <p class="<?= $dashboards['financial']['profit_trend'] >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $dashboards['financial']['profit_trend'] ?>% from last month
                </p>
            </div>
        </div>
    </div>
</div>
