<div class="card mb-3">
    <div class="card-body">
        <form class="row mb-2" method="get" onchange="this.submit()">
            <div class="row">
                <div class="col-3">
                    <h3><?= ucfirst($user['user_role']) ?> Overview for </h3>
                </div>
                <div class="col-3">
                    <select name="month" class="form-select">
                        <?php foreach ($time['months'] as $month) { ?>
                            <option value="<?= $month ?>" <?= $month == $time['month'] ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $month, 1)) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-3">
                    <select name="year" class="form-select">
                        <?php foreach ($time['years'] as $year) { ?>
                            <option value="<?= $year ?>" <?= $year == $time['year'] ? 'selected' : '' ?>><?= $year ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div> 