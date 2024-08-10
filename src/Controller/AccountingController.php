<?php
// src/Controller/AccountingController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\Model\Client;

class AccountingController {
    private $pdo;
    private $view;
    private $auth;
    private $accounting;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->view = new View();
        $this->auth = new Auth($pdo);
        $this->accounting = new Accounting($pdo);

        if (!$this->auth->check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
    }

    public function index() {
        //Redirect to /public/?page=home temporarily
        header('Location: /public/?page=home');
        exit;
    }

    public function showInvoices($client_id = false) {
        $auth = new Auth($this->pdo);

        if ($client_id) {
            // Check if user has access to the client
            if (!$auth->checkClientAccess($_SESSION['user_id'], $client_id, 'view')) {
                // If user does not have access, display an error message
                $this->view->error([
                    'title' => 'Access Denied',
                    'message' => 'You do not have permission to view this client.'
                ]);
                return;
            }
            $client_page = true;
            $client = new Client($this->pdo);
            $client_header = $client->getClientHeader($client_id);
            $data['client_header'] = $client_header['client_header'];
        } else {
            $client_page = false;
        }

        $data['card']['title'] = 'Invoices';
        $data['table']['header_rows'] = ['Number', 'Client Name', 'Scope', 'Amount', 'Date', 'Status'];


        $invoices = $this->accounting->getInvoices($client_id);
        foreach ($invoices as $invoice) {

            $client_id = $invoice['invoice_client_id'];
            $client = new Client($this->pdo);
            $client_name = $client->getClient($client_id)['client_name'];

            $invoice_number = $invoice['invoice_number'];
            $invoice_id = $invoice['invoice_id'];
            $invoice_number_display = "<a href='?page=invoice&invoice_id=$invoice_id'>$invoice_number</a>";
    

            $data['table']['body_rows'][] = [
                $invoice_number_display,
                $client_name,
                $invoice['invoice_scope'],
                $invoice['invoice_amount'],
                $invoice['invoice_date'],
                $invoice['invoice_status']
            ];
        }

        $this->view->render('simpleTable', $data, $client_page);
    }

    public function showInvoice($invoice_id) {
        $invoice = $this->accounting->getInvoice($invoice_id);
        $client_id = $invoice['invoice_client_id'];
        $client = new Client($this->pdo);
        $client_name = $client->getClient($client_id)['client_name'];
        $data = [
            'client' => $client,
            'client_header' => $client->getClientHeader($client_id)['client_header'],
        ];

        $data['card']['title'] = 'Invoice';
        $data['table']['header_rows'] = ['Field', 'Value'];
        $data['table']['body_rows'] = [
            ['Number', $invoice['invoice_number']],
            ['Client', $client_name],
            ['Scope', $invoice['invoice_scope']],
            ['Amount', $invoice['invoice_amount']],
            ['Date', $invoice['invoice_date']],
            ['Status', $invoice['invoice_status']]
        ];

        $this->view->render('simpleTable', $data, true);
    }

    public function showQuotes($client_id = false) {
        $auth = new Auth($this->pdo);

        if ($client_id) {
            // Check if user has access to the client
            if (!$auth->checkClientAccess($_SESSION['user_id'], $client_id, 'view')) {
                // If user does not have access, display an error message
                $this->view->error([
                    'title' => 'Access Denied',
                    'message' => 'You do not have permission to view this client.'
                ]);
                return;
            }
            $client_page = true;
            $client = new Client($this->pdo);
            $client_header = $client->getClientHeader($client_id);
            $data['client_header'] = $client_header['client_header'];
        } else {
            $client_page = false;
        }

        $data['card']['title'] = 'Quotes';
        $data['table']['header_rows'] = ['Number', 'Client Name', 'Scope', 'Amount', 'Date', 'Status'];

        $quotes = $this->accounting->getQuotes($client_id);
        foreach ($quotes as $quote) {
            $client_id = $quote['quote_client_id'];
            $client = new Client($this->pdo);
            $client_name = $client->getClient($client_id)['client_name'];

            $quote_number = $quote['quote_number'];
            $quote_id = $quote['quote_id'];
            $quote_number_display = "<a href='?page=quote&quote_id=$quote_id'>$quote_number</a>";

            $data['table']['body_rows'][] = [
                $quote_number_display,
                $client_name,
                $quote['quote_scope'],
                $quote['quote_amount'],
                $quote['quote_date'],
                $quote['quote_status']
            ];
        }

        $this->view->render('simpleTable', $data, $client_page);
    }
}