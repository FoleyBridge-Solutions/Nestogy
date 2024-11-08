<?php
// src/Controller/HumanResourcesController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\HumanResources;

/**
 * Human Resources Controller
 * 
 * Handles all human resources related functionality including payroll and employee time management
 */
class HumanResourcesController
{
    /** @var \PDO */
    private $pdo;
    
    /** @var View */
    private $view;
    
    /** @var Auth */
    private $auth;
    
    /** @var HumanResources */
    private $humanResources;

    /**
     * Initialize the Human Resources controller
     *
     * @param \PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->view = new View();
        $this->auth = new Auth($pdo);
        $this->humanResources = new HumanResources($pdo);
        if (!Auth::check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Main entry point for HR functionality
     *
     * @param string $hr_page The HR page to display
     * @param string $pay_period The selected pay period
     * @param int $employee_id The employee ID
     * @return void
     */
    public function index($hr_page, $pay_period, $employee_id) {
        switch ($hr_page) {
            case 'payroll':
                if (isset($pay_period)) {
                    $this->payroll($pay_period);
                } else {
                    $this->chosePayPeriod();
                }
                break;
            case 'employee_payroll':
                if (isset($employee_id)) {
                    $this->employeePayroll($employee_id, $pay_period);
                }
                break;
        }
    }

    /**
     * Display the pay period selection interface
     *
     * @return void
     */
    private function chosePayPeriod() {
        $pay_periods = $this->humanResources->getPayPeriods();
        $data['card']['title'] = 'Pay Periods';
        $data['table']['header_rows'] = ['Start Date', 'End Date', 'Select'];
        $data['table']['body_rows'] = [];
        foreach ($pay_periods as $pay_period) {
            $data['table']['body_rows'][] = [
                date('F j, Y', strtotime($pay_period['start'])),
                date('F j, Y', strtotime($pay_period['end'])),
                '<a class="btn btn-primary" href="?page=hr&hr_page=payroll&pay_period=' . $pay_period['start'] . '">Enter Pay Period</a>'
            ];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * Display payroll information for all employees in a given pay period
     *
     * @param string $pay_period The start date of the pay period
     * @return void
     */
    private function payroll($pay_period) {
        $employees = $this->humanResources->getEmployees();
        $pay_period_start = $pay_period;
        $pay_period_end = $this->humanResources->getPayPeriod($pay_period)['end'];

        $data['card']['title'] = 'Payroll for ' . $pay_period_start . ' to ' . $pay_period_end;
        $data['table']['header_rows'] = ['Employee Name', 'Pay Type', 'Hours Worked', 'Pay Rate', 'Total Pay'];
        $data['table']['body_rows'] = [];

        foreach ($employees as $employee) {
            if ($employee['user_pay_type'] == 'hourly') {

                $hours_worked = $this->humanResources->getHoursWorked($employee['user_id'], $pay_period);
                $overtime_hours = $hours_worked > 40 ? $hours_worked - 40 : 0;
                $regular_hours = $hours_worked - $overtime_hours;
                $total_pay = ($regular_hours * $employee['user_pay_rate']) + ($overtime_hours * $employee['user_pay_rate'] * 1.5);

            } elseif ($employee['user_pay_type'] == 'contractor') {

                $hours_worked = $this->humanResources->getBillableHours($employee['user_id'], $pay_period);
                $total_pay = $hours_worked * $employee['user_pay_rate'];

            } elseif ($employee['user_pay_type'] == 'salary') {

                $total_pay = $employee['user_pay_rate'] * 12 / 52;

            }
            $total_pay = round($total_pay, 2);

            $data['table']['body_rows'][] = [
                "<a href='?page=hr&hr_page=employee_payroll&pay_period=" . $pay_period . "&employee_id=" . $employee['user_id'] . "'>" . $employee['user_name'] . "</a>",
                $employee['user_pay_type'],
                $hours_worked,
                $employee['user_pay_rate'],
                "$" . $total_pay
            ];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * Display detailed payroll information for a specific employee
     *
     * @param int $employee_id The employee's ID
     * @param string $pay_period The pay period start date
     * @return void
     */
    private function employeePayroll($employee_id, $pay_period) {
        $employee = $this->humanResources->getEmployee($employee_id);
        $time_entries = $this->humanResources->getEmployeeTimeEntries($employee_id, $pay_period);

        $data['card']['title'] = $employee['user_name'] . ' Payroll for ' . $pay_period;
        $data['table']['header_rows'] = ['Date', 'Time In', 'Time Out', 'Break Start', 'Break End', 'Actions'];
        $data['table']['body_rows'] = [];
        foreach ($time_entries as $time_entry) {
            $breaks = $this->humanResources->getBreaks($time_entry['employee_time_id']);
            
            // Initialize break display values
            $break_start = 'No Break';
            $break_end = 'No Break';
            
            // If there are breaks, show the first break's start and end times
            if (!empty($breaks)) {
                $first_break = $breaks[0];  // Get the first break
                if (isset($first_break['employee_break_time_start']) && 
                    $first_break['employee_break_time_start'] != '0000-00-00 00:00:00') {
                    $break_start = date('g:i A', strtotime($first_break['employee_break_time_start'])) . 
                        ' <span class="text-muted small">(' . date('F j, Y', strtotime($first_break['employee_break_time_start'])) . ')</span>';
                }
                if (isset($first_break['employee_break_time_end']) && 
                    $first_break['employee_break_time_end'] != '0000-00-00 00:00:00') {
                    $break_end = date('g:i A', strtotime($first_break['employee_break_time_end'])) . 
                        ' <span class="text-muted small">(' . date('F j, Y', strtotime($first_break['employee_break_time_end'])) . ')</span>';
                } else {
                    $break_end = 'Not Ended';
                }
            }

            // Format end time
            if ($time_entry['employee_time_end'] == '0000-00-00 00:00:00') {
                $end_time = 'Not Ended';
            } else {
                $end_time = date('g:i A', strtotime($time_entry['employee_time_end'])) . 
                    ' <span class="text-muted small">(' . date('F j, Y', strtotime($time_entry['employee_time_end'])) . ')</span>';
            }

            $data['table']['body_rows'][] = [
                date('D, F j, Y', strtotime($time_entry['employee_time_start'])),
                date('g:i A', strtotime($time_entry['employee_time_start'])) . 
                    ' <span class="text-muted small">(' . date('F j, Y', strtotime($time_entry['employee_time_start'])) . ')</span>',
                $end_time,
                $break_start,
                $break_end,
                '<div class="btn-group" role="group" aria-label="Basic example">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" class="btn btn-primary loadModalContentBtn" data-modal-file="employee_time_edit_modal.php?employee_time_id=' . $time_entry['employee_time_id'] . '" href="#">Edit</a>
                </div>'
            ];
        }
        $this->view->render('simpleTable', $data);
    }
}
