<?php
// src/Controller/DashboardController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\Model\Client;
use Twetech\Nestogy\Model\Support;
use NumberFormatter;

/**
 * Dashboard Controller
 * 
 * Handles all dashboard-related functionality including financial, sales, and support metrics
 */
class DashboardController {
    private $view;
    private $accounting;
    private $client;
    private $support;
    private $auth;
    private $dashboards;
    private $formatter;

    /**
     * Initialize the Dashboard Controller
     *
     * @param \PDO $pdo Database connection instance
     */
    public function __construct($pdo) {
        $this->view = new View();
        $this->accounting = new Accounting($pdo);
        $this->client = new Client($pdo);
        $this->support = new Support($pdo);
        $this->auth = new Auth($pdo);
        $this->dashboards = array();
        $this->formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        if (!Auth::check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Display the dashboard index page
     *
     * @param int|null $month Current month (1-12)
     * @param int|null $year Current year
     * @return void
     */
    public function index($month = null, $year = null) {
        if ($month === null) {
            $month = date('m');
        }
        if ($year === null) {
            $year = date('Y');
        }
        $data = [
            'month' => $month,
            'year' => $year,
        ];
        $userRole = $this->auth->getUserRole();
        if ($userRole === 'admin') {
            $this->dashboards['financial'] = [];
            $this->dashboards['sales'] = [];
            $this->dashboards['support'] = [];
            $this->dashboards['recent_activities'] = $this->auth->getAllRecentActivities();
        }
        if ($userRole === 'tech') {
            $this->dashboards['support'] = [];
            $this->dashboards['recent_activities'] = $this->auth->getRecentActivitiesByUser();
        }
        
        if (isset($this->dashboards['financial'])) {
            $this->dashboards['financial'] = [
                'recievables' => $this->accounting->getRecievables($month, $year),
                'income' => $this->accounting->getIncomeTotal($month, $year),
                'unbilled_tickets' => $this->accounting->getAllUnbilledTickets($month, $year),
                'income_categories' => $this->accounting->getIncomeByCategory($month, $year),
                'expense_categories' => $this->accounting->getExpensesByCategory($month, $year),
            ];
        }
        if (isset($this->dashboards['sales'])) {
            $this->dashboards['sales'] = [
                'total_quotes' => $this->accounting->getTotalQuotes($month, $year),
                'total_quotes_accepted' => $this->accounting->getTotalQuotesAccepted($month, $year),
                'new_clients' => count($this->client->getNewClients($month, $year)),
            ];
        }
        if (isset($this->dashboards['support'])) {
            $this->dashboards['support'] = [
                'unassigned_tickets' => $this->support->getUnassignedTickets($month, $year),
                'assigned_tickets' => $this->support->getAssignedTickets($month, $year),
                'resolved_tickets' => $this->support->getResolvedTickets($month, $year),
            ];
        }
        $data = [
            'time' => [
                'month' => $month,
                'year' => $year,
                'months' => range(1, 12),
                'years' => range(date('Y'), date('Y') - 5),
            ],
            'formatter' => $this->formatter,
            'user' => [
                'user_role' => $userRole,
                'user_name' => $this->auth->getUsername(),
            ],
            'dashboards' => $this->dashboards
        ];
        if (isset($this->dashboards['financial'])) {
            $data['chart_data'] = $this->getChartData($year);
        }

        $this->view->render('dashboard/index', $data);  
    }

    /**
     * Generate chart data for financial metrics
     *
     * @param int $year Year to generate data for
     * @return array Monthly financial metrics including income, expenses, profit, and receivables
     */
    private function getChartData($year) {
        $chart_data = [];
        foreach (range(1, 12) as $month) {
            $income = round($this->accounting->getIncomeTotal($month, $year)/1000, 2);
            $expenses = round($this->accounting->getExpensesTotal($month, $year)/1000, 2);
            $recievables = round($this->accounting->getRecievables($month, $year)/1000, 2);
            $profit = round($this->accounting->getProfit($month, $year)/1000, 2);
            $chart_data[$month] = [
                'month' => $month,
                'year' => $year,
                'income' => $income,
                'expenses' => $expenses,
                'profit' => $profit,
                'recievables' => $recievables,
                'last_year_profit' => round($this->accounting->getProfit($month, $year - 1)/1000, 2),
                'estimated_profit' => 0,
            ];
        }
        return $chart_data;
    }
}
