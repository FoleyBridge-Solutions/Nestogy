<?php require_once "/var/www/nestogy/includes/inc_all_modal.php"; ?>

<?php
$employee_time_sql = "SELECT * FROM employee_times WHERE employee_time_id = ?";
$employee_time_prep = $mysqli->prepare($employee_time_sql);
$employee_time_prep->bind_param('i', $_GET['employee_time_id']);
$employee_time_prep->execute();
$employee_time_row = $employee_time_prep->get_result()->fetch_assoc();

$employee_time_break_sql = "SELECT * FROM employee_time_breaks WHERE employee_time_id = ?";
$employee_time_break_prep = $mysqli->prepare($employee_time_break_sql);
$employee_time_break_prep->bind_param('i', $_GET['employee_time_id']);
$employee_time_break_prep->execute();
$employee_time_break_row = $employee_time_break_prep->get_result()->fetch_assoc();
?>

<div class="modal-header">
    <h5 class="modal-title">Employee Ticket Clock</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body">
    <input type="hidden" name="employee_time_id" value="<?= $_GET['employee_time_id']; ?>">
    <input type="hidden" name="edit_employee_time" value="true">
    <div class="form-group">
        <label>Time In</label>
        <input type="datetime-local" class="form-control" name="employee_time_start" required value="<?= date('Y-m-d\TH:i', strtotime($employee_time_row['employee_time_start'])); ?>">
    </div>
    <div class="form-group">
        <label>Time Out</label>
        <input type="datetime-local" class="form-control" name="employee_time_end" required value="<?= date('Y-m-d\TH:i', strtotime($employee_time_row['employee_time_end'])); ?>">
    </div>
    <div class="form-group">
        <label>Break Time Start</label>
        <input type="datetime-local" class="form-control" name="employee_time_break_start" required value="<?= date('Y-m-d\TH:i', strtotime($employee_time_break_row['employee_break_time_start'])); ?>">
    </div>
    <div class="form-group">
        <label>Break Time End</label>
        <input type="datetime-local" class="form-control" name="employee_time_break_end" required value="<?= date('Y-m-d\TH:i', strtotime($employee_time_break_row['employee_break_time_end'])); ?>">
    </div>
    <button type="submit" name="edit_employee_time" class="btn btn-primary">Save</button>
</div>