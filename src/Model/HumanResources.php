<?php
// src/Model/HumanResources.php

namespace Twetech\Nestogy\Model;

use PDO;

class HumanResources {
    private $pdo;
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    public function getEmployees() {
        $query = $this->pdo->query('SELECT * FROM user_employees LEFT JOIN users ON user_employees.user_id = users.user_id');
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getEmployee($employee_id) {
        $query = $this->pdo->query('SELECT * FROM user_employees LEFT JOIN users ON user_employees.user_id = users.user_id WHERE user_employees.employee_id = :employee_id');
        $query->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
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

        return $pay_periods;
    }
    public function getPayPeriod($pay_period) {
        $pay_period_start = $pay_period;
        $pay_period_end = date('Y-m-d', strtotime('next thursday', strtotime($pay_period_start)));
        return [
            'start' => $pay_period_start,
            'end' => $pay_period_end
        ];
    }
    public function getPayroll($pay_period) {
        $start_date = $pay_period['start_date'];
        $end_date = $pay_period['end_date'];

        $employees = $this->getEmployees();
        
        $payroll = [];
        foreach ($employees as $employee) {
            $hours_worked = 0;
            $total_pay = 0;
            $break_time = 0;
        }   
    }
    public function getHoursWorked($employee_id, $pay_period) {
        $hours_worked = 0;
        
        $pay_period = $this->getPayPeriod($pay_period);
        $pay_period['end'] = $pay_period['end']." 23:59:59";

        $times = $this->pdo->prepare(
            'SELECT * FROM employee_times
            WHERE employee_id = :employee_id
            AND employee_time_start >= :start_date
            AND employee_time_end <= :end_date');
        $times->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
        $times->bindParam(':start_date', $pay_period['start'], PDO::PARAM_STR);
        $times->bindParam(':end_date', $pay_period['end'], PDO::PARAM_STR);
        $times->execute();
        $times = $times->fetchAll(PDO::FETCH_ASSOC);

        foreach ($times as $time) {
            $hours_worked += $this->getHoursWorkedForTime($time);
        }
        if ($hours_worked > 0) {
            return $hours_worked;
        } else {
            return 0;
        }
    }
    public function getBillableHours($employee_id, $pay_period) {
        return 10;
    }
    private function getHoursWorkedForTime($time) {
        $hours_worked = 0;
        $time_start = strtotime($time['employee_time_start']);
        $time_end = strtotime($time['employee_time_end']);
        $hours_worked += ($time_end - $time_start) / 3600;

        // Check if the time is running
        if ($time['employee_time_end'] == '0000-00-00 00:00:00') {
            $time_end = date('Y-m-d H:i:s');
        } else {
            $time_end = strtotime($time['employee_time_end']);
        }
        $time_diff = $time_end - $time_start;
        $hours_worked += $time_diff;

        $breaks = $this->getBreaks($time['employee_time_id']);
        $break_time = 0;
        foreach ($breaks as $break) {
            $break_time += $this->getBreakTime($break);           
        }

        $hours_worked -= $break_time;

        $hours_worked = round($hours_worked / 3600, 2);

        return $hours_worked;
    }
    private function getBreakTime($break) {
        $break_time_start = strtotime($break['employee_break_time_start']);
        $break_time_end = strtotime($break['employee_break_time_end']);
        $break_time_diff = $break_time_end - $break_time_start;
        return $break_time_diff;
    }
    private function getBreaks($employee_time_id) {
        $query = $this->pdo->prepare('SELECT * FROM employee_time_breaks
                                            WHERE employee_time_id = :employee_time_id');
        $query->bindParam(':employee_time_id', $employee_time_id, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}