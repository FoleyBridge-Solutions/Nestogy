<?php
// src/Controller/ReportsController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\Accounting;
use NumberFormatter;

/**
 * Controller class handling various financial and operational reports
 */
class ReportsController{
    /** @var \PDO */
    private $pdo;

    /** @var View */
    private $view;

    /** @var Auth */
    private $auth;

    /** @var Accounting */
    private $accounting;

    /** @var NumberFormatter */
    private $numberFormatter;

    /**
     * Initialize the Reports controller with database connection and required services
     *
     * @param \PDO $pdo Database connection instance
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->view = new View();
        $this->auth = new Auth($pdo);
        $this->accounting = new Accounting($pdo);
        $this->numberFormatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        if (!$this->auth->check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Generate sales tax report
     *
     * @return void
     */
    private function taxReport(){
        $tax_report = 
        [
            'monthly_sales' => $this->accounting->getMonthlySalesTaxReport(),
        ];
        $this->view->render('reports/tax', $tax_report);
    }

    /**
     * Generate collections report
     *
     * @return void
     */
    private function collectionsReport(){
        $collections_report = $this->accounting->getCollectionsReport();
        $this->view->render('reports/collections', $collections_report);
    }

    /**
     * Generate income summary report for current month
     *
     * @return void
     */
    private function incomeSummaryReport(){
        $income_summary = $this->accounting->getIncomeByCategory(date('m'), date('Y'));
        $this->view->render('reports/tableWithChart', $income_summary);
    }

    /**
     * Generate income by client report for current month
     *
     * @return void
     */
    private function incomeByClientReport(){
        $income_by_client = $this->accounting->getIncomeTotalByClientReport(date('m'), date('Y'));
        $this->view->render('reports/tableWithChart', $income_by_client);
    }

    /**
     * Generate subscriptions summary report
     *
     * @return void
     */
    private function subscriptionsSummaryReport(){
        $subscriptions_summary = $this->accounting->getSubscriptionsSummaryReport();
        $this->view->render('reports/tableWithChart', $subscriptions_summary);
    }

    /**
     * Generate expenses summary report
     *
     * @return void
     */
    private function expensesSummaryReport(){
        $expenses_summary = $this->accounting->getExpensesTotalSummaryReport();
        $this->view->render('reports/tableWithChart', $expenses_summary);
    }

    /**
     * Generate expenses by category report
     *
     * @return void
     */
    private function expensesByCategoryReport(){
        $expenses_by_category = $this->accounting->getExpensesTotalByCategoryReport();
        $this->view->render('reports/tableWithChart', $expenses_by_category);
    }

    /**
     * Generate profit summary report
     *
     * @return void
     */
    private function profitSummaryReport(){
        $profit_summary = $this->accounting->getProfitSummaryReport();
        $this->view->render('reports/tableWithChart', $profit_summary);
    }

    /**
     * Generate balance sheet report
     *
     * @return void
     */
    private function balanceSheetReport(){
        $balance_sheet = $this->accounting->getBalanceSheetReport();
        $this->view->render('reports/tableWithChart', $balance_sheet);
    }

    /**
     * Generate cash flow report
     *
     * @return void
     */
    private function cashFlowReport(){
        $cash_flow = $this->accounting->getCashFlowReport();
        $this->view->render('reports/tableWithChart', $cash_flow);
    }

    /**
     * Generate profit and loss report
     *
     * @return void
     */
    private function profitLossReport(){

        $data = [
            'table' => [
                'header_rows' => ['', 'TOTAL'],
            ],
            'report' => $this->accounting->getProfitLossReport()
        ];

        $this->view->render('reports/profit_loss', $data);
    }

    /**
     * Generate report of unbilled tickets
     *
     * @return void
     */
    private function unbilledTicketsReport(){
        $unbilled_tickets = $this->accounting->getUnbilledTicketsReport(); // get the unbilled tickets
        $data = [
            'table' => [
                'header_rows' => ['Ticket Number', 'Client', 'Agent', 'Subject', 'Date', 'Status', 'Action']
            ],
        ];
        foreach($unbilled_tickets as $ticket){
            $data['table']['body_rows'][] = [
                $ticket['ticket_id'],
                $ticket['client_name'],
                $ticket['user_name']??'Unassigned',
                $ticket['ticket_subject'],
                $ticket['ticket_created_at'],
                $ticket['ticket_status_name'],
                '<a href="?page=ticket&&ticket_id='.$ticket['ticket_id'].'">View</a>'
            ];
        }
        $this->view->render('reports/tableWithChart', $data);
    }

    /**
     * Generate tickets summary report
     *
     * @return void
     */
    private function ticketsSummaryReport(){
        $tickets_summary = $this->accounting->getTicketsSummaryReport();
        $this->view->render('reports/tableWithChart', $tickets_summary);
    }

    /**
     * Generate tickets by client report
     *
     * @return void
     */
    private function ticketsByClientReport(){
        $tickets_by_client = $this->accounting->getTicketsByClientReport();
        $this->view->render('reports/tableWithChart', $tickets_by_client);
    }

    /**
     * Generate tickets by agent report
     *
     * @return void
     */
    private function ticketsByAgentReport(){
        $tickets_by_agent = $this->accounting->getTicketsByAgentReport();
        $this->view->render('reports/tableWithChart', $tickets_by_agent);
    }

    /**
     * Generate tickets by category report
     *
     * @return void
     */
    private function ticketsByCategoryReport(){
        $tickets_by_category = $this->accounting->getTicketsByCategoryReport();
        $this->view->render('reports/tableWithChart', $tickets_by_category);
    }

    /**
     * Route to appropriate report based on request parameter
     *
     * @param string $report The type of report to generate
     * @return void
     */
    public function index($report){
        switch($report){
            case 'income_summary':
                $this->incomeSummaryReport();
                break;
            case 'income_by_client':
                $this->incomeByClientReport();
                break;
            case 'income_by_category':
                $this->incomeByCategoryReport();
                break;
            case 'subscriptions_summary':
                $this->subscriptionsSummaryReport();
                break;
            case 'expenses_summary':
                $this->expensesSummaryReport();
                break;
            case 'expenses_by_category':
                $this->expensesByCategoryReport();
                break;
            case 'profit_loss':
                $this->profitLossReport();
                break;
            case 'balance_sheet':
                $this->balanceSheetReport();
                break;
            case 'cash_flow':
                $this->cashFlowReport();
                break;
            case 'profit_and_loss':
                $this->profitAndLossReport();
                break;
            case 'tax_summary':
                $this->taxReport();
                break;
            case 'collections':
                $this->collectionsReport();
                break;
            case 'tickets_unbilled':
                $this->unbilledTicketsReport();
                break;
            case 'tickets_summary':
                $this->ticketsSummaryReport();
                break;
            case 'tickets_by_client':
                $this->ticketsByClientReport();
                break;
            case 'tickets_by_agent':
                $this->ticketsByAgentReport();
                break;
            case 'tickets_by_category':
                $this->ticketsByCategoryReport();
                break;
            default:
                header('Location: /');
                exit;
        }
    }
}