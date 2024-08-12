<?php

global $mysqli, $name, $ip, $user_agent, $user_id;


/*
 * ITFlow - GET/POST request handler for employees
 */


if (isset($_POST['link_employee'])) {
    $user_id = $_POST['user_id'];
    $sql = "INSERT INTO user_employees (user_id, user_pay_type, user_pay_rate, user_max_hours, user_payroll_id) VALUES (?, 'hourly', 7.25, 50, 0)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        referWithAlert("Employee linked successfully.", "success");
    } else {
        referWithAlert("Error linking employee: " . $stmt->error, "danger");
    }
    $stmt->close();
}

if (isset($_POST['unlink_employee'])) {
    $user_id = $_POST['user_id'];
    $sql = "DELETE FROM user_employees WHERE user_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        referWithAlert("Employee unlinked successfully.", "success");
    } else {
        referWithAlert("Error unlinking employee: " . $stmt->error, "danger");
    }
    $stmt->close();
}

if (isset($_POST['update_employee'])) {
    $user_id = $_POST['user_id'];
    $user_pay_type = $_POST['user_pay_type'];
    $user_pay_rate = $_POST['user_pay_rate'];
    $user_max_hours = $_POST['user_max_hours_per_week'] ?? 0;

    $sql = "UPDATE user_employees SET 
        user_pay_type = ?, 
        user_pay_rate = ?, 
        user_max_hours = ? 
        WHERE user_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sdii", $user_pay_type, $user_pay_rate, $user_max_hours, $user_id);

    if ($stmt->execute()) {
        referWithAlert("Employee updated successfully.", "success");
    } else {
        referWithAlert("Error updating employee: " . $stmt->error, "danger");
    }
    $stmt->close();
}
if (isset($_POST['employee_time_in'])) {
    $user_id = $_POST['user_id'];
    $time_notes = $_POST['time_notes'] ?? "";
    $time_in = date("Y-m-d H:i:s");

    $sql = "INSERT INTO employee_times (employee_id, employee_time_start) VALUES (?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("is", $user_id, $time_in);

    if ($stmt->execute()) {
        referWithAlert("Employee clocked in successfully.", "success");
    } else {
        referWithAlert("Error clocking in employee: " . $stmt->error, "danger");
    }
    $stmt->close();
}

if (isset($_POST['employee_time_out'])) {
    $time_id = $_POST['time_id'] ?? null;

    if (!$time_id) {
        referWithAlert("Error clocking out: No time ID found.", "danger");
        return;
    }

    $time_out = date("Y-m-d H:i:s");

    $sql = "UPDATE employee_times SET employee_time_end = ? WHERE employee_time_id = ?";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        referWithAlert("Error preparing statement: " . $mysqli->error, "danger");
        return;
    }

    $stmt->bind_param("si", $time_out, $time_id);

    if ($stmt->execute()) {
        referWithAlert("Employee clocked out successfully.", "success");
    } else {
        referWithAlert("Error clocking out employee: " . $stmt->error, "danger");
    }
    $stmt->close();
}

if (isset($_POST['employee_break_start'])) {
    $time_id = $_POST['time_id'] ?? null;
    $break_notes = $_POST['break_notes'] ?? "";
    $break_start = date("Y-m-d H:i:s");

    $sql = "INSERT INTO employee_time_breaks (employee_time_id, employee_break_time_start, employee_break_time_end, employee_break_time_notes) VALUES (?, ?, '0000-00-00 00:00:00', ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("iss", $time_id, $break_start, $break_notes);

    if ($stmt->execute()) {
        referWithAlert("Employee break started successfully.", "success");
    } else {
        referWithAlert("Error starting break: " . $stmt->error, "danger");
    }
    $stmt->close();
}

if (isset($_POST['employee_break_end'])) {
    $break_id = $_POST['break_id'] ?? null;

    if (!$break_id) {
        referWithAlert("Error ending break: No break ID found.", "danger");
        return;
    }

    $break_end = date("Y-m-d H:i:s");

    $sql = "UPDATE employee_time_breaks SET employee_break_time_end = ? WHERE employee_time_break_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("si", $break_end, $break_id);

    if ($stmt->execute()) {
        referWithAlert("Employee break ended successfully.", "success");
    } else {
        referWithAlert("Error ending break: " . $stmt->error, "danger");
    }
    $stmt->close();
}
