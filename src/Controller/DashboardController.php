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
    private $components = [];
    private $availableComponents = [
        'admin' => ['welcome', 'financial', 'sales', 'support', 'recent_activities'],
        'tech' => ['welcome', 'support', 'recent_activities'],
        // Add other roles as needed
    ];
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
        $this->formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        if (!Auth::check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
        // Initialize components based on user role
        $this->initializeComponents();
    }

    /**
     * Initialize components based on user role
     */
    private function initializeComponents() {
        $userRole = $this->auth->getUserRole();
        if (!isset($this->availableComponents[$userRole])) {
            return;
        }

        foreach ($this->availableComponents[$userRole] as $component) {
            $this->components[$component] = true;
        }
    }

    /**
     * This function prepares the base data and loads data for enabled components
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

        $data = $this->prepareBaseData($month, $year);
        
        // Load data for enabled components
        foreach ($this->components as $component => $enabled) {
            if ($enabled) {
                $data = $this->loadComponentData($component, $data, $month, $year);
            }
        }

        $this->view->render('dashboard/index', $data);
    }

    /**
     * Prepare base data for the dashboard
     * 
     * @param int $month Current month
     * @param int $year Current year
     * @return array Base data
     */
    private function prepareBaseData($month, $year) {
        return [
            'time' => [
                'month' => $month,
                'year' => $year,
                'months' => range(1, 12),
                'years' => range(date('Y'), date('Y') - 5),
            ],
            'formatter' => $this->formatter,
            'user' => [
                'user_role' => $this->auth->getUserRole(),
                'user_name' => $this->auth->getUsername(),
            ],
            'components' => $this->components,
            'dashboards' => []
        ];
    }

    /**
     * Load data for a specific component
     * 
     * @param string $component Component name
     * @param array $data Base data
     * @param int $month Current month
     * @param int $year Current year
     * @return array Component data
     */
    private function loadComponentData($component, $data, $month, $year) {
        switch ($component) {
            case 'financial':
                $data['dashboards']['financial'] = $this->getFinancialData($month, $year);
                $data['chart_data'] = $this->getChartData($year);
                break;

            case 'sales':
                $data['dashboards']['sales'] = $this->getSalesData($month, $year);
                break;

            case 'support':
                $data['dashboards']['support'] = $this->getSupportData($month, $year);
                break;

            case 'recent_activities':
                $data['recent_activities'] = $this->getActivitiesData();
                break;
        }

        return $data;
    }

    /**
     * Get financial data for the dashboard
     * 
     * @param int $month Current month
     * @param int $year Current year
     * @return array Financial data
     */
    private function getFinancialData($month, $year) {
        return [
            'recievables' => $this->accounting->getRecievables($month, $year),
            'income' => $this->accounting->getIncomeTotal($month, $year),
            'unbilled_tickets' => $this->accounting->getAllUnbilledTickets($month, $year),
            'income_categories' => $this->accounting->getIncomeByCategory($month, $year),
            'expense_categories' => $this->accounting->getExpensesByCategory($month, $year),
            'revenue' => $this->accounting->getRevenue($month, $year),
            'revenue_trend' => $this->accounting->getRevenueTrend($month, $year),
            'expenses' => $this->accounting->getExpensesTotal($month, $year),
            'expenses_trend' => $this->accounting->getExpensesTrend($month, $year),
            'profit' => $this->accounting->getProfit($month, $year),
            'profit_trend' => $this->accounting->getProfitTrend($month, $year)
        ];
    }

    /**
     * Get sales data for the dashboard
     * 
     * @param int $month Current month
     * @param int $year Current year
     * @return array Sales data
     */
    private function getSalesData($month, $year) {
        return [
            'total_orders' => $this->accounting->getTotalQuotes($month, $year),
            'orders_trend' => $this->accounting->getQuotesTrend($month, $year),
            'avg_order_value' => $this->accounting->getAverageQuoteValue($month, $year),
            'aov_trend' => $this->accounting->getAverageQuoteValueTrend($month, $year),
            'conversion_rate' => $this->accounting->getQuoteConversionRate($month, $year),
            'conversion_trend' => $this->accounting->getQuoteConversionTrend($month, $year)
        ];
    }

    /**
     * Get support data for the dashboard
     * 
     * @param int $month Current month
     * @param int $year Current year
     * @return array Support data
     */
    private function getSupportData($month, $year) {
        return [
            'open_tickets' => $this->support->getUnassignedTickets($month, $year),
            'tickets_trend' => $this->support->getTicketsTrend($month, $year),
            'avg_response_time' => $this->support->getAverageResponseTime($month, $year),
            'response_trend' => $this->support->getResponseTimeTrend($month, $year),
            'satisfaction' => $this->support->getCustomerSatisfaction($month, $year),
            'satisfaction_trend' => $this->support->getSatisfactionTrend($month, $year)
        ];
    }

    /**
     * Get activities data for the dashboard
     * 
     * @return array Activities data
     */
    private function getActivitiesData() {
        return $this->auth->getUserRole() === 'admin' 
            ? $this->auth->getAllRecentActivities()
            : $this->auth->getRecentActivitiesByUser();
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
