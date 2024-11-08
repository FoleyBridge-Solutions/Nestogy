<?php
// src/Model/HumanResources.php

namespace Twetech\Nestogy\Model;

use PDO;

/**
 * HumanResources class handles employee time tracking and payroll operations
 */
class HumanResources {
    /** @var PDO Database connection instance */
    private $pdo;

    /**
     * Constructor for HumanResources class
     *
     * @param PDO $pdo Database connection instance
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves all employees from the database
     *
     * @return array List of employees with their user information
     */
    public function getEmployees() {
        $query = $this->pdo->query('SELECT * FROM user_employees LEFT JOIN users ON user_employees.user_id = users.user_id');
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves a specific employee by their ID
     *
     * @param int $employee_id The ID of the employee to retrieve
     * @return array|false Employee data or false if not found
     */
    public function getEmployee($employee_id) {
        $query = $this->pdo->prepare('SELECT * FROM user_employees LEFT JOIN users ON user_employees.user_id = users.user_id WHERE user_employees.user_id = :employee_id');
        $query->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Calculates all pay periods based on time entries in the database
     *
     * @return array List of pay periods with start and end dates
     */
    public function getPayPeriods() {
        // Find the first pay period (weekly Friday to Thursday) in the database based on when the first time was entered
        $first_time = $this->pdo->query('SELECT MIN(employee_time_start) as first_time FROM employee_times');
        $first_time = $first_time->fetch(PDO::FETCH_ASSOC);
        $first_time = $first_time['first_time'];
        
        // Find the last pay period (weekly Friday to Thursday) in the database based on when the last time was entered
        $last_time = $this->pdo->query('SELECT MAX(employee_time_end) as last_time FROM employee_times');
        $last_time = $last_time->fetch(PDO::FETCH_ASSOC);
        $last_time = $last_time['last_time'];

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

        // Sort the pay periods by start date
        usort($pay_periods, function($a, $b) {
            return strtotime($b['start']) - strtotime($a['start']);
        });

        return $pay_periods;
    }

    /**
     * Gets the pay period information for a specific start date
     *
     * @param string $pay_period Start date of the pay period (Y-m-d format)
     * @return array Pay period start and end dates
     */
    public function getPayPeriod($pay_period) {
        $pay_period_start = $pay_period;
        $pay_period_end = date('Y-m-d', strtotime('next thursday', strtotime($pay_period_start)));
        return [
            'start' => $pay_period_start,
            'end' => $pay_period_end
        ];
    }

    /**
     * Calculates total hours worked for an employee in a specific pay period
     *
     * @param int    $employee_id Employee ID
     * @param string $pay_period  Start date of the pay period (Y-m-d format)
     * @return float Total hours worked
     */
    public function getHoursWorked($employee_id, $pay_period) {
        $hours_worked = 0;
        
        $pay_period = $this->getPayPeriod($pay_period);
        $pay_period['end'] = $pay_period['end']." 23:59:59";

        // Fetch times that have ended within the pay period
        $times = $this->pdo->prepare(
            'SELECT * FROM employee_times
            WHERE employee_id = :employee_id
            AND employee_time_start >= :start_date
            AND employee_time_end <= :end_date
            AND employee_time_end != "0000-00-00 00:00:00"');
        $times->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        $times->bindParam(':start_date', $pay_period['start'], PDO::PARAM_STR);
        $times->bindParam(':end_date', $pay_period['end'], PDO::PARAM_STR);
        $times->execute();
        $times = $times->fetchAll(PDO::FETCH_ASSOC);

        foreach ($times as $time) {
            $hours_worked += $this->getHoursWorkedForTime($time);
        }

        // Handle ongoing times separately
        $ongoing_times = $this->pdo->prepare(
            'SELECT * FROM employee_times
            WHERE employee_id = :employee_id
            AND employee_time_start >= :start_date
            AND employee_time_start <= :end_date
            AND employee_time_end = "0000-00-00 00:00:00"');
        $ongoing_times->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        $ongoing_times->bindParam(':start_date', $pay_period['start'], PDO::PARAM_STR);
        $ongoing_times->bindParam(':end_date', $pay_period['end'], PDO::PARAM_STR);
        $ongoing_times->execute();
        $ongoing_times = $ongoing_times->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ongoing_times as $time) {
            $hours_worked += $this->getHoursWorkedForTime($time);
        }

        return $hours_worked > 0 ? $hours_worked : 0;
    }

    /**
     * Calculates billable hours for an employee in a specific pay period
     *
     * @param int    $employee_id Employee ID
     * @param string $pay_period  Start date of the pay period (Y-m-d format)
     * @return float Total billable hours
     */
    public function getBillableHours($employee_id, $pay_period) {
        return 0;
    }

    /**
     * Calculates hours worked for a specific time entry
     *
     * @param array $time Time entry data
     * @return float Hours worked for this time entry
     */
    private function getHoursWorkedForTime($time) {
        $time_start = strtotime($time['employee_time_start']);
        
        // Check if the time is running
        if ($time['employee_time_end'] == '0000-00-00 00:00:00') {
            $time_end = time(); // Use current time if the employee is still clocked in
        } else {
            $time_end = strtotime($time['employee_time_end']);
        }

        // Calculate the total time worked in seconds
        $time_diff = $time_end - $time_start;

        // Calculate the total break time in seconds
        $breaks = $this->getBreaks($time['employee_time_id']);
        $break_time = 0;
        foreach ($breaks as $break) {
            $break_time += $this->getBreakTime($break);           
        }

        // Subtract break time from total time worked
        $total_worked_time = $time_diff - $break_time;

        // Convert to hours and round to two decimal places
        $hours_worked = round($total_worked_time / 3600, 2);

        return $hours_worked;
    }

    /**
     * Calculates the duration of a break
     *
     * @param array $break Break entry data
     * @return int Break duration in seconds
     */
    public function getBreakTime($break) {
        $break_time_start = strtotime($break['employee_break_time_start']);
        $break_time_end = strtotime($break['employee_break_time_end']);
        $break_time_diff = $break_time_end - $break_time_start;
        return $break_time_diff;
    }

    /**
     * Retrieves all breaks for a specific time entry
     *
     * @param int $employee_time_id Time entry ID
     * @return array List of breaks
     */
    public function getBreaks($employee_time_id) {
        $query = $this->pdo->prepare('SELECT * FROM employee_time_breaks WHERE employee_time_id = :employee_time_id');
        $query->bindParam(':employee_time_id', $employee_time_id, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all time entries for an employee within a pay period
     *
     * @param int    $employee_id Employee ID
     * @param string $pay_period  Start date of the pay period (Y-m-d format)
     * @return array List of time entries
     */
    public function getEmployeeTimeEntries($employee_id, $pay_period) {
        // Get all time entries for an employee within a pay period
        $pay_period = $this->getPayPeriod($pay_period);
        $pay_period['end'] = $pay_period['end'] . " 23:59:59";

        $query = $this->pdo->prepare('SELECT * FROM employee_times
                                        WHERE employee_id = :employee_id
                                        AND employee_time_start >= :start_date
                                        AND (employee_time_end <= :end_date OR employee_time_end = "0000-00-00 00:00:00")');
        $query->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        $query->bindParam(':start_date', $pay_period['start'], PDO::PARAM_STR);
        $query->bindParam(':end_date', $pay_period['end'], PDO::PARAM_STR);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all breaks for an employee within a pay period
     *
     * @param int    $employee_id Employee ID
     * @param string $pay_period  Start date of the pay period (Y-m-d format)
     * @return array List of breaks
     */
    public function getEmployeeBreaks($employee_id, $pay_period) {
        // Get all breaks for an employee within a pay period
        $pay_period = $this->getPayPeriod($pay_period);
        $pay_period['end'] = $pay_period['end'] . " 23:59:59";

        $query = $this->pdo->prepare('SELECT * FROM employee_time_breaks
                                        WHERE employee_time_id IN (SELECT employee_time_id FROM employee_times
                                        WHERE employee_id = :employee_id
                                        AND employee_time_start >= :start_date
                                        AND (employee_time_end <= :end_date OR employee_time_end = "0000-00-00 00:00:00"))');
        $query->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        $query->bindParam(':start_date', $pay_period['start'], PDO::PARAM_STR);
        $query->bindParam(':end_date', $pay_period['end'], PDO::PARAM_STR);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
