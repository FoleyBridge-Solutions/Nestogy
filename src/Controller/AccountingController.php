<?php
// src/Controller/AccountingController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\Model\Client;
use NumberFormatter;


class AccountingController {
    private $pdo;
    private $view;
    private $auth;
    private $accounting;
    private $client;
    private $currency_format;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->view = new View();
        $this->auth = new Auth($pdo);
        $this->accounting = new Accounting($pdo);
        $this->client = new Client($pdo);
        $this->currency_format = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
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
        if ($client_page) {
            $data['table']['header_rows'] = ['Number', 'Scope','Balance','Total', 'Date', 'Status','Actions'];
            $data['action'] = [
                'title' => 'Create Invoice',
                'modal' => 'invoice_add_modal.php?client_id='.$client_id
            ];
            $data['return_page'] = [
                'name' => 'Invoices',
                'link' => 'invoices'
            ];
        } else {
            $data['table']['header_rows'] = ['Number', 'Client Name', 'Scope','Balance', 'Total', 'Date', 'Status','Actions'];
            $data['action'] = [
                'title' => 'Create Invoice',
                'modal' => 'invoice_add_modal.php'
            ];
        }

        $invoices = $this->accounting->getInvoices($client_id);
        foreach ($invoices as $invoice) {
            // Get the client name
            $client_id = $invoice['invoice_client_id'];
            $client_name = $invoice['client_name'];
            $client_name_display = "<a class='btn btn-label-primary btn-sm' data-bs-toggle='tooltip' data-bs-placement='top' title='View Invoices for $client_name' href='?page=invoices&client_id=$client_id'>$client_name</a>";
            
            // Get the invoice number to display with a link to the invoice
            $invoice_number = $invoice['invoice_number'];
            $invoice_id = $invoice['invoice_id'];
            $invoice_prefix = $invoice['invoice_prefix'];
            $invoice_number_display = "<a class='btn btn-label-primary btn-sm' data-bs-toggle='tooltip' data-bs-placement='top' title='View $invoice_prefix $invoice_number' href='?page=invoice&invoice_id=$invoice_id'>$invoice_number</a>";

            $invoice_balance = $this->accounting->getInvoiceBalance($invoice_id);
            $invoice_total = $this->accounting->getInvoiceTotal($invoice_id);

            // Check if the invoice is status sent and due date is in the past
            if ($invoice['invoice_status'] == 'Sent' && $invoice['invoice_due'] < date('Y-m-d')) {
                $invoice['invoice_status'] .= ' & Overdue';
            }
            $actions = [];
            if ($invoice['invoice_status'] == 'Draft') {
                $actions[] = '<button class="btn btn-label-warning btn-sm sendInvoiceEmailBtn" data-invoice-id="'.$invoice_id.'" data-bs-toggle="tooltip" data-bs-placement="top" title="Send Invoice Email"><i class="fas fa-fw fa-envelope mr-2"></i>Send Email</button>';
                $actions[] = '<button class="btn btn-label-danger btn-sm cancelInvoiceBtn" data-invoice-id="'.$invoice_id.'" data-bs-toggle="tooltip" data-bs-placement="top" title="Cancel Invoice" style="display: none;"><i class="fas fa-fw fa-times mr-2"></i>Cancel</button>';
                $actions[] = '<button class="btn btn-label-success btn-sm loadModalContentBtn addPaymentBtn" data-modal-file="invoice_payment_add_modal.php?invoice_id='.$invoice_id.'&balance='.$invoice_balance.'" data-bs-toggle="tooltip" data-bs-placement="top" title="Add Payment Manually" style="display: none;"><i class="fas fa-fw fa-money-bill-alt mr-2"></i>Add Payment</button>';
            }
            if ($invoice['invoice_status'] != 'Draft') {
                $actions[] = '<button class="btn btn-label-success btn-sm loadModalContentBtn addPaymentBtn" data-modal-file="invoice_payment_add_modal.php?invoice_id='.$invoice_id.'&balance='.$invoice_balance.'" data-bs-toggle="tooltip" data-bs-placement="top" title="Add Payment Manually"><i class="fas fa-fw fa-money-bill-alt mr-2"></i>Add Payment</button>';
                $actions[] = '<button class="btn btn-label-danger btn-sm cancelInvoiceBtn" data-invoice-id="'.$invoice_id.'" data-bs-toggle="tooltip" data-bs-placement="top" title="Cancel Invoice"><i class="fas fa-fw fa-times mr-2"></i>Cancel</button>';
            }
            $actions_string = implode(' ', $actions);
        
            if ($client_page) {
                $data['table']['body_rows'][] = [
                    $invoice_number_display,    
                    $invoice['invoice_scope'],
                    numfmt_format_currency($this->currency_format, $invoice_balance, 'USD'),
                    numfmt_format_currency($this->currency_format, $invoice_total, 'USD'),
                    $invoice['invoice_date'],
                    $invoice['invoice_status'],
                    $actions_string
                ];
            } else {
                $data['table']['body_rows'][] = [
                    $invoice_number_display,
                    $client_name_display,
                    $invoice['invoice_scope'],
                    numfmt_format_currency($this->currency_format, $invoice_balance, 'USD'),
                    numfmt_format_currency($this->currency_format, $invoice_total, 'USD'),
                    $invoice['invoice_date'],
                    $invoice['invoice_status'],
                    $actions_string
                ];
            }
        }
        $this->view->render('simpleTable', $data, $client_page);
    }
    public function showInvoice($invoice_id) {
        $invoice = $this->accounting->getInvoice($invoice_id);
        $client_id = $invoice['invoice_client_id'];
        $invoice_tickets = $this->accounting->getTicketsByInvoice($invoice_id);
        $unbilled_tickets = $this->accounting->getUnbilledTickets($invoice_id);
        $client = new Client($this->pdo);
        $data = [
            'client' => $client,
            'client_header' => $client->getClientHeader($client_id)['client_header'],
            'invoice' => $invoice,
            'tickets' => $invoice_tickets,
            'unbilled_tickets' => $unbilled_tickets,
            'company' => $this->auth->getCompany(),
            'all_products' => $this->accounting->getProductsAutocomplete(),
            'all_taxes' => $this->accounting->getTaxes(),
            'return_page' => [
                'name' => 'Invoices',
                'link' => 'invoices'
            ]
        ];

        $this->view->render('invoice', $data, true);
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
            $data['action'] = [
                'title' => 'Create Quote',
                'modal' => 'quote_add_modal.php?client_id='.$client_id
            ];
            $data['table']['header_rows'] = ['Number','Scope', 'Amount', 'Date', 'Status'];
            $data['return_page'] = [
                'name' => 'Quotes',
                'link' => 'quotes'
            ];

        } else {
            $client_page = false;
            $data['action'] = [
                'title'=> 'Create Quote',
                'modal'=> 'quote_add_modal.php'
            ];
            $data['table']['header_rows'] = ['Number','Client Name','Scope','Amount','Date','Status'];
        }

        $data['card']['title'] = 'Quotes';

        $quotes = $this->accounting->getQuotes($client_id);
        foreach ($quotes as $quote) {
            $client_id = $quote['quote_client_id'];
            $client = new Client($this->pdo);
            $client_name = $client->getClient($client_id)['client_name'];
            $client_name_display = "<a class='btn btn-label-primary btn-sm' data-bs-toggle='tooltip' data-bs-placement='top' title='View Quotes for $client_name' href='?page=quotes&client_id=$client_id'>$client_name</a>";
            $quote_number = $quote['quote_number'];
            $quote_id = $quote['quote_id'];
            $quote_prefix = $quote['quote_prefix'];
            $quote_number_display = "<a class='btn btn-label-primary btn-sm' data-bs-toggle='tooltip' data-bs-placement='top' title='View $quote_prefix $quote_number' href='?page=quote&quote_id=$quote_id'>$quote_number</a>";


            // Check if the quote is status sent and due expire is in the past
            if ($quote['quote_status'] == 'Sent' && $quote['quote_expire'] < date('Y-m-d')) {
                $quote['quote_status'] .= ' & Expired';
            }

            if ($client_page) {
                $data['table']['body_rows'][] = [
                    $quote_number_display,
                    $quote['quote_scope'],
                    numfmt_format_currency($this->currency_format, $quote['quote_amount'], 'USD'),
                    $quote['quote_date'],
                    $quote['quote_status']
                ];                
            } else {
                $data['table']['body_rows'][] = [
                    $quote_number_display,
                    $client_name_display,
                    $quote['quote_scope'],
                    numfmt_format_currency($this->currency_format, $quote['quote_amount'], 'USD'),
                    $quote['quote_date'],
                    $quote['quote_status']
                    ];
            }
        }

        $this->view->render('simpleTable', $data, $client_page);
    }
    public function showQuote($quote_id) {
        $quote = $this->accounting->getQuote($quote_id);
        $client_id = $quote['quote_client_id'];
        $client = new Client($this->pdo);
        $data = [
            'client' => $client,
            'client_header' => $client->getClientHeader($client_id)['client_header'],
            'quote' => $quote,
            'company' => $this->auth->getCompany(),
            'all_products' => $this->accounting->getProductsAutocomplete(),
            'all_taxes' => $this->accounting->getTaxes(),
            'return_page' => [
                'name' => 'Quotes',
                'link' => 'quotes'
            ]
        ];
        $this->view->render('invoice', $data, true);
    }
    public function showSubscriptions($client_id = false) {
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
            $data['table']['header_rows'] = ['Product', 'Quantity','Total Price', 'Updated'];

        } else {
            $data['table']['header_rows'] = ['Client', 'Product', 'Quantity','Total Price', 'Updated'];
            $client_page = false;
        }

        $data['card']['title'] = 'Subscriptions';

        $subscriptions = $this->accounting->getSubscriptions($client_id);
        foreach ($subscriptions as $subscription) {
            $client = new Client($this->pdo);
            $client_name = $client->getClient($subscription['subscription_client_id'])['client_name'];
            $total_product_price = $subscription['product_price'] * $subscription['subscription_product_quantity'];
            $product_name = $this->accounting->getProduct($subscription['subscription_product_id'])['product_name'];

            if ($client_page) {
                $data['table']['body_rows'][] = [
                    '<a href="?page=product&product_id='.$subscription['subscription_product_id'].'">'.$product_name.'</a>',
                    $subscription['subscription_product_quantity'],
                    $total_product_price,
                    $subscription['subscription_updated']
                ];
                $data['return_page'] = [
                    'name' => 'Subscriptions',
                    'link' => 'subscriptions'
                ];
            } else {
                $data['table']['body_rows'][] = [
                    '<a href="?page=subscriptions&client_id='.$subscription['subscription_client_id'].'">'.$client_name.'</a>',
                    '<a href="?page=product&product_id='.$subscription['subscription_product_id'].'">'.$product_name.'</a>',
                    $subscription['subscription_product_quantity'],
                    $total_product_price,
                    $subscription['subscription_updated']
                ];
            }
        }
        $data['action'] = [
            'title' => 'Add Subscription',
            'modal' => 'subscription_add_modal.php?client_id=' . $client_id
        ];
        $this->view->render('simpleTable', $data, $client_page);
    }
    public function showSubscription($subscription_id) {
        $subscription = $this->accounting->getSubscription($subscription_id);
        $this->view->render('simpleTable', $subscription, true);
    }
    public function showPayments($client_id = false) {
        $payments = $this->accounting->getPayments($client_id);
        
        $auth = new Auth($this->pdo);
        if (!$auth->checkClientAccess($_SESSION['user_id'], $client_id, 'view')) {
            // If user does not have access, display an error message
            $this->view->error([
                'title' => 'Access Denied',
                'message' => 'You do not have permission to view this client.'
            ]);
            return;
        }

        if ($client_id) {
            $client_page = true;
            $client = new Client($this->pdo);
            $client_header = $client->getClientHeader($client_id);
            $data['client_header'] = $client_header['client_header'];
        } else {
            $client_page = false;
        }

        $data['card']['title'] = 'Payments';
        if ($client_page) {
            $data['table']['header_rows'] = ['Reference', 'Amount', 'Date'];
            $data['return_page'] = [
                'name' => 'Payments',
                'link' => 'payments'
            ];
        } else {
            $data['table']['header_rows'] = ['Client', 'Reference', 'Amount', 'Date'];
        }
        foreach ($payments as $payment) {
            if ($client_page) {//if client page is true, dont show client name row
                $data['table']['body_rows'][] = [
                    "<a href='?page=payment&payment_reference=$payment[payment_reference]'>$payment[payment_reference]</a>",
                    $payment['payment_amount'],
                    $payment['payment_date'],
                ];
            } else {//if client page is false, show client name row
                $data['table']['body_rows'][] = [
                    "<a href='?page=payments&client_id=" . $payment['client_id'] . "'>" . $payment['client_name'] . "</a>",
                    "<a href='?page=payment&payment_reference=" . $payment['payment_reference'] . "'>" . $payment['payment_reference'] . "</a>",
                    $payment['payment_amount'],
                    $payment['payment_date'],
                ];
            }
        }
        $this->view->render('simpleTable', $data, $client_page);
    }
    public function showPayment($reference) {
        $payment = $this->accounting->getPaymentsByReference($reference);

        $auth = new Auth($this->pdo);
        if (!$auth->checkClientAccess($_SESSION['user_id'], $payment['payment_client_id'], 'view')) {
            // If user does not have access, display an error message
            $this->view->error([
                'title' => 'Access Denied',
                'message' => 'You do not have permission to view this client.'
            ]);
            return;
        }

        $client_page = FALSE;
        $data['card']['title'] = 'Payment';
        $data['table']['header_rows'] = ['Payment ID', 'Invoice ID', 'Amount', 'Date'];
        foreach ($payment as $payment) {
            $data['table']['body_rows'][] = [
                $payment['payment_id'],
                $payment['payment_invoice_id'],
                $payment['payment_amount'],
                $payment['payment_date'],
            ];
        }
        $this->view->render('simpleTable', $data, $client_page);
    }
    public function makePayment() {
        $clients = $this->client->getClients();
        $categories = $this->accounting->getPaymentCategories();
        $accounts = $this->accounting->getPaymentAccounts();
        $data = [
            'clients' => $clients,
            'categories' => $categories,
            'accounts' => $accounts
        ];

        $this->view->render('makePayment', $data);
    }
    public function showProducts() {
        $products = $this->accounting->getProducts();
        $data['card']['title'] = 'Products';
        $data['table']['header_rows'] = ['Name', 'Description', 'Price'];
        foreach ($products as $product) {
            $data['table']['body_rows'][] = [
                '<a href="?page=product&product_id='.$product['product_id'].'">'.$product['product_name'].'</a>',
                $product['product_description'],
                $product['product_price'],
            ];
        }
        $this->view->render('simpleTable', $data);
    }
    public function showProduct($product_id) {
        $product = $this->accounting->getProduct($product_id);
        $taxes = $this->accounting->getTaxes();
        $categories = $this->accounting->getCategories();
        $data = [
            'product' => $product,
            'taxes' => $taxes,
            'categories' => $categories
        ];
        $this->view->render('editProduct', $data);
    }
    public function showStatement($client_id) {
        $data = [
            'statement' => $this->accounting->getStatement($client_id),
            'all_clients' => $this->client->getClients(),
            'client_header' => $this->client->getClientHeader($client_id)['client_header'],
            'return_page' => [
                'name' => 'Collections',
                'link' => 'report&report=collections'
            ]
        ];
        $this->view->render('statement', $data, true);
    }

}