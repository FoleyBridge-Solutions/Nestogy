<?php
// src/Controller/HumanResourcesController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\HumanResources;

class HumanResourcesController
{
    private $pdo;
    private $view;
    private $auth;
    private $humanResources;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->view = new View();
        $this->auth = new Auth($pdo);
        $this->humanResources = new HumanResources($pdo);
    }
    public function index($hr_page, $pay_period) {
        switch ($hr_page) {
            case 'payroll':
                if (isset($pay_period)) {
                    $this->payroll($pay_period);
                } else {
                    $this->chosePayPeriod();
                }
                break;
        }
    }
    private function chosePayPeriod() {
        $pay_periods = $this->humanResources->getPayPeriods();
        $data['card']['title'] = 'Pay Periods';
        $data['table']['header_rows'] = ['Start Date', 'End Date', 'Select'];
        $data['table']['body_rows'] = [];
        foreach ($pay_periods as $pay_period) {
            $data['table']['body_rows'][] = [
                $pay_period['start'],
                $pay_period['end'],
                '<a href="?page=hr&hr_page=payroll&pay_period=' . $pay_period['start'] . '">Select</a>'
            ];
        }
        $this->view->render('simpleTable', $data);
    }

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
                $hours_worked = $this->humanResources->getHoursWorked($employee['user_id'], $pay_period);
                $total_pay = $employee['user_pay_rate'];
            }
            $total_pay = round($total_pay, 2);
            $data['table']['body_rows'][] = 
                [
                    $employee['user_name'],
                    $employee['user_pay_type'],
                    $hours_worked,
                    $employee['user_pay_rate'],
                    "$" . $total_pay
                ];
        }
        $this->view->render('simpleTable', $data);
    }
}
