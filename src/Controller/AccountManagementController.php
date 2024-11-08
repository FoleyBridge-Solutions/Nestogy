<?php
// src/Controller/AccountManagementController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\Client;
use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\Model\Support;
use NumberFormatter;

/**
 * Account Management Controller
 * 
 * Handles various account management functionalities including invoices,
 * tickets, client management, and sales pipeline operations.
 */
class AccountManagementController {
    private $view;
    private $pdo;
    private $auth;
    private $accounting;
    private $sales_pipeline;
    private $client;
    private $data;
    private $support;
    private $currency_format;

    /**
     * Initialize the Account Management Controller
     *
     * @param \PDO $pdo Database connection instance
     */
    public function __construct($pdo) {
        $this->view = new View();
        $this->auth = new Auth($pdo);
        $this->accounting = new Accounting($pdo);
        $this->support = new Support($pdo);
        $this->client = new Client($pdo);
        $this->currency_format = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        if (!$this->auth->check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Display aging invoices in a table format
     *
     * @return void
     */
    public function agingInvoices() {
        $aging_invoices = $this->accounting->getAgingInvoices();
        $this->data['card']['title'] = 'Aging Invoices';
        $this->data['table']['header_rows'] = ['Number', 'Client Name', 'Scope','Balance', 'Total', 'Date', 'Status'];
        foreach ($aging_invoices as $invoice) {
            $this->data['table']['body_rows'][] = [
                'number' => "<a class='btn btn-primary' href='?page=invoice&invoice_id=" . $invoice['invoice_id'] . "'>" . $invoice['invoice_number'] . "</a>",
                'client_name' => $invoice['client_name'],
                'scope' => $invoice['invoice_scope'],
                'balance' => $this->currency_format->format($this->accounting->getInvoiceBalance($invoice['invoice_id'])),
                'total' => $this->currency_format->format($this->accounting->getInvoiceTotal($invoice['invoice_id'])),
                'date' => $invoice['invoice_date'],
                'status' => $invoice['invoice_status']
            ];
        }
        $this->view->render('simpleTable', $this->data);
    }

    /**
     * Display closed support tickets in a table format
     *
     * @return void
     */
    public function closedTickets() {
        $closed_tickets = $this->support->getClosedTickets();
        $this->data['card']['title'] = 'Closed Tickets';
        $this->data['table']['header_rows'] = ['Number', 'Client Name', 'Scope','Balance', 'Total', 'Date', 'Status','Actions'];
        foreach ($closed_tickets as $ticket) {
            $this->data['table']['body_rows'][] = [
                'number' => $ticket['ticket_number'],
                'client_name' => $ticket['client_name'],
                'scope' => $ticket['ticket_scope'],
                'balance' => $ticket['ticket_balance'],
                'total' => $ticket['ticket_total'],
                'date' => $ticket['ticket_date'],
                'status' => $ticket['ticket_status_name'],
                'actions' => '<a href="ticket.php?ticket_id=' . $ticket['ticket_id'] . '">View</a>'
            ];
        }
        $this->view->render('simpleTable', $this->data);
    }

    /**
     * Display aging support tickets in a table format
     *
     * @return void
     */
    public function agingTickets() {
        $aging_tickets = $this->support->getAgingTickets();
        $this->data['card']['title'] = 'Aging Tickets';
        $this->data['table']['header_rows'] = ['Number', 'Client Name', 'Date', 'Status','Actions'];
        foreach ($aging_tickets as $ticket) {
            $this->data['table']['body_rows'][] = [
                'number' => '<a class="btn btn-primary" href="?page=ticket&ticket_id=' . $ticket['ticket_id'] . '">' . $ticket['ticket_number'] . '</a>',
                'client_name' => $ticket['client_name'],
                'date' => date('j M Y', strtotime($ticket['ticket_created_at'])),
                'status' => $ticket['ticket_status_name'],
                'actions' => '<a href="ticket.php?ticket_id=' . $ticket['ticket_id'] . '">View</a>'
            ];
        }
        $this->view->render('simpleTable', $this->data);
    }

    /**
     * Display clients without login credentials in a table format
     *
     * @return void
     */
    public function clientsWithoutLogin() {
        $clients_without_login = $this->client->getClientsWithoutLogin();
        $this->data['card']['title'] = 'Clients Without Login';
        $this->data['table']['header_rows'] = ['Client Name', 'Client Email', 'Client Phone', 'Client Address', 'Client City', 'Client State', 'Client Zip', 'Client Country'];
        foreach ($clients_without_login as $client) {
            $this->data['table']['body_rows'][] = [
                'client_name' => $client['client_name'],
                'client_email' => $client['client_email'],
                'client_phone' => $client['client_phone'],
                'client_address' => $client['client_address'],
                'client_city' => $client['client_city'],
                'client_state' => $client['client_state'],
                'client_zip' => $client['client_zip'],
                'client_country' => $client['client_country']
            ];
        }
        $this->view->render('simpleTable', $this->data);
    }

    /**
     * Display clients without subscriptions in a table format
     *
     * @param int|null $subscription_id Optional subscription ID filter
     * @return void
     */
    public function clientsWithoutSubscription($subscription_id = null) {
        $clients_without_subscription = $this->client->clientsWithoutSubscription();
        $this->data['card']['title'] = 'Clients Without Subscription';
        $this->data['table']['header_rows'] = ['Client Name', 'Subscriptions available', 'Subscriptions subscribed'];
        $this->data['all_subscriptions'] = $this->accounting->getAvailableSubscriptions();
        foreach ($clients_without_subscription as $client) {
            $this->data['table']['body_rows'][] = [
                'client_name' => '<a href="?page=client&client_id=' . $client['client_id'] . '">' . $client['client_name'] . '</a>',
                'available_subscriptions' => 'Count: ' . count($this->accounting->getAvailableSubscriptions($client['client_id'])) . '<ul><li>' . implode('</li><li>', array_column($this->accounting->getAvailableSubscriptions($client['client_id']), 'product_name')) . '</li></ul>',
                'subscribed_subscriptions' => 'Count: ' . count($this->accounting->getSubscribedSubscriptions($client['client_id'])) . '<ul><li>' . implode('</li><li>', array_column($this->accounting->getSubscribedSubscriptions($client['client_id']), 'product_name')) . '</li></ul>'
            ];
        }
        $this->view->render('clients_without_subscription', $this->data);
    }

    /**
     * Display sales pipeline information including opportunities,
     * leads, qualified leads, contacts, landings, and meetings
     *
     * @return void
     */
    public function salesPipeline() {
        $this->data['opportunities'] = $this->accounting->getOpportunities();   
        $this->data['leads'] = $this->client->getLeads();
        $this->data['qualified_leads'] = $this->client->getQualifiedLeads();
        $this->data['contacts'] = $this->client->getSalesContacts();
        $this->data['landings'] = $this->client->getSalesLandings();
        $this->data['meetings'] = $this->support->getSalesMeetings();
        $this->view->render('sales_pipeline', $this->data);
    }
}
