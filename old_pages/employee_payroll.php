<?php
require "/var/www/portal.twe.tech/includes/inc_all.php";

// Get all users
$users = mysqli_query($mysqli,
    "SELECT * FROM user_employees
    LEFT JOIN users ON user_employees.user_id = users.user_id"
);

// Get all user times
$times = mysqli_query($mysqli,
    "SELECT * FROM employee_times"
);

// Find the first pay period (weekly Friday to Thursday) in the database based on when the first time was entered
$first_time = mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT MIN(employee_time_start) as first_time FROM employee_times"
))['first_time'];

// Find the last pay period (weekly Friday to Thursday) in the database based on when the last time was entered
$last_time = mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT MAX(employee_time_end) as last_time FROM employee_times"
))['last_time'];

// Calculate the pay periods between the first and last time
$pay_periods = [];
$pay_period_start = date('Y-m-d', strtotime('last friday', strtotime($first_time)));
$pay_period_end = date('Y-m-d', strtotime('next thursday', strtotime($pay_period_start)));

while ($pay_period_start <= $last_time) {
    $pay_periods[] = [
        'start' => $pay_period_start,
        'end' => $pay_period_end
    ];

    // Move to the next pay period
    $pay_period_start = date('Y-m-d', strtotime('next friday', strtotime($pay_period_start)));
    $pay_period_end = date('Y-m-d', strtotime('next thursday', strtotime($pay_period_start)));
}

// Get the pay period from the query string
$pay_period_start = $_GET['pay-period'] ?? $pay_periods[0]['start'];
$pay_period_end = null;

// Find the correct end date for the selected start date
foreach ($pay_periods as $period) {
    if ($period['start'] == $pay_period_start) {
        $pay_period_end = $period['end'];
        break;
    }
}

// Get all the employees for the selected pay period
$pay_period_employees_sql = "SELECT * FROM user_employees
    LEFT JOIN users ON user_employees.user_id = users.user_id
    WHERE user_employees.user_id IN (
        SELECT employee_id FROM employee_times
        WHERE employee_time_start >= '$pay_period_start'
        AND employee_time_end <= '$pay_period_end'
)";
$pay_period_employees = mysqli_query($mysqli, $pay_period_employees_sql);


?>

<div class="row">
    <div class="col">
        <div class="row">
            <div class="card">
                <div class="card-header">
                    <h3>Pay periods</h3>
                </div>
                <div class="card-body">
                    <form action="employee_payroll.php" method="get">
                        <div class="form-group">
                            <label for="pay-period">Select a pay period</label>
                            <select name="pay-period" id="pay-period" class="form-control" onchange="this.form.submit()">
                                <?php foreach ($pay_periods as $period): ?>
                                    <option value="<?= $period['start'] ?>" <?= $period['start'] == $pay_period_start ? 'selected' : '' ?>>
                                        <?= $period['start'] ?> - <?= $period['end'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                    <span>
                        <?php
                        echo $pay_period_start;
                        echo ' - ';
                        echo $pay_period_end;
                        ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="card">
                <div class="card-header">
                    <h3>Employees</h3>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Employee Name</th>
                            <th>Pay Type</th>
                            <th>Hours Worked</th>
                            <th>Billable Hours</th>
                            <th>Pay Rate</th>
                            <th>Total Pay</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($employee = mysqli_fetch_assoc($pay_period_employees)): ?>
                            <tr>
                                <td>
                                    <?= $employee['user_name'] ?>
                                </td>
                                <td>
                                    <?= $employee['user_pay_type'] ?>
                                </td>
                                <td>
                                    <?php
                                    $hours_worked = 0;
                                    $total_pay = 0;
                                    $break_time = 0;
                                    $time_running_icon = false;
                                    $break_icon = false;

                                    $employee_times_sql = "SELECT * FROM employee_times
                                        WHERE employee_id = {$employee['user_id']}
                                        AND employee_time_start >= '$pay_period_start'
                                        AND employee_time_end <= '$pay_period_end 23:59:59'
                                        ";
                                    $employee_times = mysqli_query($mysqli, $employee_times_sql);


                                    while ($time = mysqli_fetch_assoc($employee_times)) {
                                        $time_start = strtotime($time['employee_time_start']);
                                        $time_end = strtotime($time['employee_time_end']);

                                        // Check if the time is running
                                        if ($time['employee_time_end'] == '0000-00-00 00:00:00') {
                                            //double check to see if time is in current pay period, skip if not in current pay period
                                            if ($time_start < strtotime($pay_period_start) || $time_start > strtotime($pay_period_end . ' 23:59:59')) {
                                                continue;
                                            }
                                            $time_end = time();
                                            $time_running_icon = true;
                                        } else {
                                            $time_end = strtotime($time['employee_time_end']);
                                        }
                                        $time_diff = $time_end - $time_start;
                                        $hours_worked += $time_diff;


                                        // Check if the time has an associated break
                                        $employee_breaks_sql = "SELECT * FROM employee_time_breaks
                                            WHERE employee_time_id = {$time['employee_time_id']}";
                                        $employee_breaks = mysqli_query($mysqli, $employee_breaks_sql);

                                        while ($break = mysqli_fetch_assoc($employee_breaks)) {
                                            $break_time_start = strtotime($break['employee_break_time_start']);
                                            $break_time_end = strtotime($break['employee_break_time_end']);

                                            $break_time_diff = $break_time_end - $break_time_start;
                                            // Check if the break is running
                                            if ($break['employee_break_time_end'] == '0000-00-00 00:00:00') {
                                                $break_icon = true;
                                            }
                                            $break_time += $break_time_diff;
                                        }
                                    }

                                    // convert seconds to hours
                                    $hours_worked = round($hours_worked / 3600, 3);
                                    $break_time = round($break_time / 3600, 1);
                                    $pay_rate = $employee['user_pay_rate'];
                                    $total_pay = ($hours_worked - $break_time) * $pay_rate;
                                    ?>
                                        <div class="row">
                                            <?= $hours_worked - $break_time?> payroll hours
                                            <?php if ($time_running_icon): ?>
                                                <i class="fas fa-stopwatch"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="row small">
                                            <?= $break_time ?> hours on break
                                            <?php if ($break_icon): ?>
                                                <i class="fas fa-coffee"></i>
                                            <?php endif; ?>
                                        </div>
                                </td>
                                <td>
                                </td>
                                <td>
                                    <?= $employee['user_pay_rate'] ?>
                                </td>
                                <td>
                                    <?= $total_pay ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require "/var/www/portal.twe.tech/includes/footer.php";
