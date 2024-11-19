<?php
// src/Controller/AccountingController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\Model\Client;
use Twetech\Nestogy\Model\Notification;
use NumberFormatter;
use PDO;

/**
 * Controller handling all accounting-related functionality
 */
class AccountingController {
    private $pdo;
    private $view;
    private $auth;
    private $accounting;
    private $client;
    private $notifications;
    private $currency_format;

    /**
     * Initialize the Accounting Controller with database connection and required services
     *
     * @param PDO $pdo Database connection
     */
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

    /**
     * Default index action - currently redirects to home page
     *
     * @return void
     */
    public function index() {
        //Redirect to /public/?page=home temporarily
        header('Location: /public/?page=home');
        exit;
    }

    /**
     * Display list of invoices, optionally filtered by client
     *
     * @param int|false $client_id Optional client ID to filter invoices
     * @return void
     */
    public function showInvoices($client_id = false) {
        $statuses = [
            'Draft' => [
                'icon' => 'fas fa-fw fa-file-alt mr-2',
                'class' => 'warning'
            ],
            'Sent' => [
                'icon' => 'fas fa-fw fa-paper-plane mr-2',
                'class' => 'success'
            ],
            'Viewed' => [
                'icon' => 'fas fa-fw fa-eye mr-2',
                'class' => 'info'
            ],
            'Paid' => [
                'icon' => 'fas fa-fw fa-check-circle mr-2',
                'class' => 'success'
            ],
            'Cancelled' => [
                'icon' => 'fas fa-fw fa-times mr-2',
                'class' => 'danger'
            ],
            'Overdue' => [
                'icon' => 'fas fa-fw fa-exclamation-triangle mr-2',
                'class' => 'danger'
            ]
        ];
        $auth = new Auth($this->pdo);

        if ($client_id) {
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

            // Calculate the date difference
            $invoice_date = new \DateTime($invoice['invoice_date']);
            $current_date = new \DateTime();
            $date_diff = $current_date->diff($invoice_date)->days;

            // Determine the date display class
            $date_class = $date_diff > 30 ? 'text-danger' : '';

            // Format the date with the appropriate class
            $formatted_date = "<span class='$date_class'>" . $invoice['invoice_date'] . "</span>";

            $actions = '<div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                Actions
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">';

            if ($invoice['invoice_status'] == 'Draft') {
                $actions .= '<li><a href="/post.php?email_invoice='.$invoice_id.'" class="dropdown-item sendInvoiceEmailBtn">Send Email</a></li>';
                $actions .= '<li><a href="/post.php?cancel_invoice='.$invoice_id.'" class="dropdown-item">Cancel</a></li>';
            } else {
                if ($invoice['invoice_status'] != 'Cancelled') {
                    if ($invoice['invoice_status'] != 'Paid') {
                        $actions .= '<li><a href="/post.php?cancel_invoice='.$invoice_id.'" class="dropdown-item">Cancel</a></li>';
                        $actions .= '<li><a href="/post.php?email_invoice='.$invoice_id.'" class="dropdown-item">Resend Email</a></li>';
                        $actions .= '<li><button class="dropdown-item loadModalContentBtn addPaymentBtn" data-modal-file="invoice_payment_add_modal.php?invoice_id='.$invoice_id.'&balance='.$invoice_balance.'">Add Payment</button></li>';
                    }
                    if ($invoice['invoice_status'] != 'Sent') {
                    }
                } else {
                    $actions .= '<li><a href="/post.php?delete_invoice='.$invoice_id.'" class="dropdown-item">Delete</a></li>';
                }
            }

            $actions .= '</ul></div>';

            $invoice_status_display = "<span class='badge bg-label-".$statuses[$invoice['invoice_status']]['class']."'>
            <i class='".$statuses[$invoice['invoice_status']]['icon']." mr-2'></i>".$invoice['invoice_status']."</span>";
        
            if ($client_page) {
                $data['table']['body_rows'][] = [
                    $invoice_number_display,    
                    $invoice['invoice_scope'],
                    numfmt_format_currency($this->currency_format, $invoice_balance, 'USD'),
                    numfmt_format_currency($this->currency_format, $invoice_total, 'USD'),
                    $formatted_date,
                    $invoice_status_display,
                    $actions
                ];
            } else {
                $data['table']['body_rows'][] = [
                    $invoice_number_display,
                    $client_name_display,
                    $invoice['invoice_scope'],
                    numfmt_format_currency($this->currency_format, $invoice_balance, 'USD'),
                    numfmt_format_currency($this->currency_format, $invoice_total, 'USD'),
                    $formatted_date,
                    $invoice_status_display,
                    $actions
                ];
            }
        }
        $this->view->render('simpleTable', $data, $client_page);
    }

    /**
     * Display detailed view of a specific invoice
     *
     * @param int $invoice_id Invoice ID to display
     * @return void
     */
    public function showInvoice($invoice_id) {
        $invoice = $this->accounting->getInvoice($invoice_id);
        $client_id = $invoice['invoice_client_id'];
        $client = new Client($this->pdo);
        $data = [
            'client' => $client,
            'client_header' => $client->getClientHeader($client_id)['client_header'],
            'invoice' => $invoice,
            'invoice_balance' => $this->accounting->getInvoiceBalance($invoice_id),
            'tickets' => $this->accounting->getTicketsByInvoice($invoice_id),
            'unbilled_tickets' => $this->accounting->getUnbilledTickets($invoice_id),
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

    /**
     * Display list of quotes, optionally filtered by client
     *
     * @param int|false $client_id Optional client ID to filter quotes
     * @return void
     */
    public function showQuotes($client_id = false) {
        $auth = new Auth($this->pdo);

        if ($client_id) {
            $client_page = true;
            $client = new Client($this->pdo);
            $client_header = $client->getClientHeader($client_id);
            $data['client_header'] = $client_header['client_header'];
            $data['action'] = [
                'title' => 'Create Quote',
                'modal' => 'quote_add_modal.php?client_id='.$client_id
            ];
            $data['table']['header_rows'] = ['Number','Scope', 'Amount', 'Date', 'Status','Actions'];
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
            $data['table']['header_rows'] = ['Number','Client Name','Scope','Amount','Date','Status','Actions'];
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
            $quote_amount = $this->accounting->getQuoteAmount($quote_id);
            $quote_prefix = $quote['quote_prefix'];
            if ($quote['quote_status'] != "Draft") {
                $resend_quote_text = "Resend Quote";
                $resend_quote_icon = "fas fa-fw fa-redo mr-2";
                $resend_quote_class = "label-warning";
            } else {
                $resend_quote_text = "Send Quote";
                $resend_quote_icon = "fas fa-fw fa-paper-plane mr-2";
                $resend_quote_class = "success";
            }
            $quote_number_display = "<a class='btn btn-label-primary btn-sm' data-bs-toggle='tooltip' data-bs-placement='top' title='View $quote_prefix $quote_number' href='?page=quote&quote_id=$quote_id'>$quote_number</a>";
            $actions = [];
            $actions[] = '<button class="btn btn-label-primary btn-sm loadModalContentBtn" data-bs-target="#dynamicModal" data-modal-file="quote_edit_modal.php?quote_id='.$quote_id.'" data-bs-toggle="modal">
            <i class="fas fa-fw fa-edit mr-2"></i>Edit
            </button>';
            $actions[] = '<a href="/post.php?email_quote='.$quote_id.'" class="btn btn-'.$resend_quote_class.' btn-sm">
            <i class="'.$resend_quote_icon.' mr-2"></i>'.$resend_quote_text.'
            </a>';
            $actions[] = '<a href="/post.php?delete_quote='.$quote_id.'" class="btn btn-label-danger btn-sm">
            <i class="fas fa-fw fa-trash mr-2"></i>Delete
            </a>';
            $actions_string = implode(' ', $actions);

            // Check if the quote is status sent and due expire is in the past
            if ($quote['quote_status'] == 'Sent' && $quote['quote_expire'] < date('Y-m-d')) {
                $quote['quote_status'] .= ' & Expired';
            }

            if ($client_page) {
                $data['table']['body_rows'][] = [
                    $quote_number_display,
                    $quote['quote_scope'],
                    numfmt_format_currency($this->currency_format, $quote_amount, 'USD'),
                    date('M j, Y', strtotime($quote['quote_date'])),
                    $quote['quote_status'],
                    $actions_string
                ];                
            } else {
                $data['table']['body_rows'][] = [
                    $quote_number_display,
                    $client_name_display,
                    $quote['quote_scope'],
                    numfmt_format_currency($this->currency_format, $quote_amount, 'USD'),
                    date('M j, Y', strtotime($quote['quote_date'])),
                    $quote['quote_status'],
                    $actions_string
                    ];
            }
        }

        $this->view->render('simpleTable', $data, $client_page);
    }

    /**
     * Display detailed view of a specific quote
     *
     * @param int $quote_id Quote ID to display
     * @return void
     */
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

    /**
     * Display list of subscriptions, optionally filtered by client
     *
     * @param int|false $client_id Optional client ID to filter subscriptions
     * @return void
     */
    public function showSubscriptions($client_id = false) {
        $auth = new Auth($this->pdo);

        if ($client_id) {
            $client_page = true;
            $client = new Client($this->pdo);
            $client_header = $client->getClientHeader($client_id);
            $data['client_header'] = $client_header['client_header'];
            $data['table']['header_rows'] = ['Product', 'Quantity','Total Price', 'Last Billed','Term', 'Actions'];

        } else {
            $data['table']['header_rows'] = ['Client', 'Product', 'Quantity','Total Price', 'Last Billed', 'Term', 'Actions'];
            $client_page = false;
        }

        $data['card']['title'] = 'Subscriptions';

        $subscriptions = $this->accounting->getSubscriptions($client_id);
        $subscription_total = [];
        foreach ($subscriptions as $subscription) {
            $tax_rate = $subscription['tax_percent'];
            $product_subtotal = $subscription['product_price'] * $subscription['subscription_product_quantity'];
            $client_taxable = $subscription['client_taxable'] == 1 ? true : false;
            $product_tax = $product_subtotal * ($tax_rate / 100);
            if ($client_taxable) {
                $product_total = $product_subtotal + $product_tax;
            } else {
                $product_total = $product_subtotal;
            }
            $product_name = $subscription['product_name'];
            $client_name = $subscription['client_name'];
            $actions = [];
            $actions[] = '<button class="btn btn-label-primary btn-sm loadModalContentBtn" data-bs-target="#dynamicModal" data-modal-file="subscription_edit_modal.php?subscription_id='.$subscription['subscription_id'].'" data-bs-toggle="modal">Edit</button>';
            $actions[] = '<a href="/post.php?delete_subscription='.$subscription['subscription_id'].'" class="btn btn-label-danger btn-sm">Delete</a>';
            $actions_string = implode(' ', $actions);

            // Add this line to initialize the array key if it doesn't exist:
            if (!isset($subscription_total[$subscription['subscription_term']])) {
                $subscription_total[$subscription['subscription_term']] = 0;
            }

            // Update the total:
            $subscription_total[$subscription['subscription_term']] += $product_total;

            if ($client_page) {
                $data['table']['body_rows'][] = [
                    '<a href="?page=product&product_id='.$subscription['subscription_product_id'].'">'.$product_name.'</a>',
                    $subscription['subscription_product_quantity'],
                    numfmt_format_currency($this->currency_format, $product_total, 'USD'),
                    $subscription['subscription_last_billed'],
                    ucwords($subscription['subscription_term']),
                    $actions_string
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
                    numfmt_format_currency($this->currency_format, $product_total, 'USD'),
                    $subscription['subscription_last_billed'],
                    ucwords($subscription['subscription_term']),
                    $actions_string
                ];
            }
        }
        foreach ($subscription_total as $term => $total) {
            $data['table']['footer_row'][] = "<p>Total for $term: ".numfmt_format_currency($this->currency_format, $total, 'USD')."</p>";
        }
        $data["table"]["footer_row"][] = "<p>Monthly Average: ".numfmt_format_currency($this->currency_format, $subscription_total['monthly'] + ($subscription_total['yearly']/12), 'USD')."</p>";
        $data['action'] = [
            [
                'title' => 'Add Subscription',
                'modal' => 'subscription_add_modal.php?client_id=' . $client_id
            ],
            [
                'title' => 'Bill Subscription',
                'modal' => 'subscription_bill_modal.php?client_id=' . $client_id
            ]
        ];
        $this->view->render('simpleTable', $data, $client_page);
    }

    /**
     * Display detailed view of a specific subscription
     *
     * @param int $subscription_id Subscription ID to display
     * @return void
     */
    public function showSubscription($subscription_id) {
        $subscription = $this->accounting->getSubscription($subscription_id);
        $this->view->render('simpleTable',$subscription, true);
    }

    /**
     * Display list of payments, optionally filtered by client
     *
     * @param int|false $client_id Optional client ID to filter payments
     * @return void
     */
    public function showPayments($client_id = false) {
        $payments = $this->accounting->getPayments($client_id);
        
        $auth = new Auth($this->pdo);

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
            $data['table']['header_rows'] = ['Method', 'Reference', 'Amount', 'Invoice', 'Date', 'Reconciled'];
            $data['return_page'] = [
                'name' => 'Payments',
                'link' => 'payments'
            ];
        } else {
            $data['table']['header_rows'] = ['Client', 'Method', 'Reference', 'Amount', 'Invoice', 'Date', 'Reconciled'];
        }
        foreach ($payments as $payment) {

            // Find how many times this payment reference has been used
            $payment_reference_count = $this->accounting->getPaymentReferenceCount($payment['payment_reference']);


            if ($client_page) {//if client page is true, dont show client name row
                $data['table']['body_rows'][] = [
                    $payment['payment_method'],
                    " (1 of " . $payment_reference_count . ")<a href='?page=payment&payment_reference=" . $payment['payment_reference'] . "'>" . $payment['payment_reference'] . "</a>",
                    numfmt_format_currency($this->currency_format, $payment['payment_amount'], 'USD'),
                    "<a href='?page=invoice&invoice_id=" . $payment['payment_invoice_id'] . "'>" . $payment['payment_invoice_id'] . "</a>",
                    $payment['payment_date'],
                    $payment['plaid_transaction_id'] ? 'Yes' : 'No'
                ];
            } else {//if client page is false, show client name row
                $data['table']['body_rows'][] = [
                    "<a href='?page=payments&client_id=" . $payment['client_id'] . "'>" . $payment['client_name'] . "</a>",
                    $payment['payment_method'],
                    " (1 of " . $payment_reference_count . ")<a href='?page=payment&payment_reference=" . $payment['payment_reference'] . "'>" . $payment['payment_reference'] . "</a>",
                    numfmt_format_currency($this->currency_format, $payment['payment_amount'], 'USD'),
                    "<a href='?page=invoice&invoice_id=" . $payment['payment_invoice_id'] . "'>" . $payment['payment_invoice_id'] . "</a>",
                    $payment['payment_date'],
                    $payment['plaid_transaction_id'] ? 'Yes' : 'No'
                ];
            }
        }
        $this->view->render('simpleTable', $data, $client_page);
    }

    /**
     * Display detailed view of payments by reference number
     *
     * @param string $reference Payment reference number
     * @return void
     */
    public function showPayment($reference) {
        $payment = $this->accounting->getPaymentsByReference($reference);
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

    /**
     * Display payment creation form, optionally pre-filled with bank transaction
     *
     * @param int|null $bank_transaction_id Optional bank transaction ID to pre-fill form
     * @return void
     */
    public function makePayment($bank_transaction_id = null) {

        $clients = $this->client->getClients();
        $categories = $this->accounting->getPaymentCategories();
        $accounts = $this->accounting->getPaymentAccounts();
        $data = [
            'clients' => $clients,
            'categories' => $categories,
            'accounts' => $accounts
        ];

        if($bank_transaction_id){
            $data['transaction'] = $this->accounting->getBankTransaction($bank_transaction_id);
        }

        $this->view->render('makePayment', $data);
    }

    /**
     * Display list of all products
     *
     * @return void
     */
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
        $data['action'] = [
            'title' => 'Add Product',
            'modal' => 'product_add_modal.php'
        ];
        $this->view->render('simpleTable', $data);
    }

    /**
     * Display detailed view of a specific product
     *
     * @param int $product_id Product ID to display
     * @return void
     */
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

    /**
     * Display statement for a specific client
     *
     * @param int $client_id Client ID to generate statement for
     * @return void
     */
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

    /**
     * Display list of all accounts
     *
     * @return void
     */
    public function showAccounts() {
        $accounts = $this->accounting->getAccounts();
        $data['card']['title'] = 'Accounts';
        $data['table']['header_rows'] = ['Name', 'Balance','Linked to Plaid'];
        foreach ($accounts as $account) {
            $data['table']['body_rows'][] = [
                "<a href='?page=account&account_id=" . $account['account_id'] . "'>" . $account['account_name'] . "</a>",
                numfmt_format_currency($this->currency_format, 0, 'USD'),
                isset($account['plaid_id']) ? 'Yes' : 'No'
            ];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * Display detailed view of a specific account
     *
     * @param int $account_id Account ID to display
     * @return void
     */
    public function showAccount($account_id) {
        $data = [
            'account' => $this->accounting->getAccount($account_id),
            'balance' => 0,
            'plaid_status' => $this->accounting->checkPlaidLinkStatus($account_id),
            'transactions' => $this->accounting->getAccountTransactions($account_id),
        ];
        $this->view->render('account', $data);
    }

    /**
     * Display list of all expenses
     *
     * @return void
     */
    public function showExpenses() {
        $expenses = $this->accounting->getExpensesTotal();
        $data['card']['title'] = 'Expenses';
        $data['table']['header_rows'] = ['Date', 'Amount', 'Category', 'Description'];
        foreach ($expenses as $expense) {
            $data['table']['body_rows'][] = [
                $expense['expense_date'],
                $expense['expense_amount'],
                $expense['category_name'],
                $expense['expense_description']
            ];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * Display detailed view of a specific expense
     *
     * @param int $expense_id Expense ID to display
     * @return void
     */
    public function showExpense($expense_id) {
        $expense = $this->accounting->getExpense($expense_id);
        $data['card']['title'] = 'Expense';
        $data['table']['header_rows'] = ['Date', 'Amount', 'Category', 'Description'];
        $data['table']['body_rows'][] = [
            $expense['expense_date'],
            $expense['expense_amount'],
            $expense['category_name'],
            $expense['expense_description']
        ];
        $this->view->render('simpleTable', $data);
    }

    /**
     * Display list of unreconciled bank transactions
     *
     * @return void
     */
    public function showUnreconciledTransactions($type) {
        $transactions = $this->accounting->getUnreconciledTransactions($type);
        $data['card']['title'] = 'Unreconciled ' . ucfirst($type);
        $data['table']['header_rows'] = ['Date', 'Amount', 'Type', 'Name', 'Reconciled'];
        $data['header_cards'] = [];
        $bank_accounts = $this->accounting->getPlaidAccounts();
        foreach ($bank_accounts as $bank_account) {

            if ($bank_account['plaid_access_token'] == null) {
                $plaid_status = 'Unlinked';
                $plaid_icon = 'plus';
                $plaid_label = 'Link';
            } else {
                $plaid_status = 'Linked';
                $plaid_icon = 'sync';
                $plaid_label = 'Resync';
            }
            $data['header_cards'][] = [
                'title' => $bank_account['plaid_name'] ?? $bank_account['plaid_official_name'] ?? $bank_account['account_name'],
                'body' => '
                <div class="row">
                    <div class="col-md-6">
                        <p>Available Balance: ' . numfmt_format_currency($this->currency_format, $bank_account['plaid_balance_available'], 'USD') . '<br>
                        Current Balance: ' . numfmt_format_currency($this->currency_format, $bank_account['plaid_balance_current'], 'USD') . '</p>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-label-primary btn-sm loadModalContentBtn" data-bs-target="#dynamicModal" data-modal-file="resync_account_modal.php?account_id=' . $bank_account['account_id'] . '&plaid_status=' . $plaid_status . '" data-bs-toggle="modal">
                        <i class="bx bx-' . $plaid_icon . ' me-1"></i>
                        ' . $plaid_label . '
                        </button>
                        <p class="small text-muted">Last Update: ' . date('F j, Y @ g:i a', strtotime($bank_account['plaid_last_update'])) . '</p>
                    </div>
                </div>
                '
            ];
        }
        foreach ($transactions as $transaction) {
            if ($transaction['reconciled'] == 1) {
                continue;
            } else {
                $reconciled = '<button class="btn btn-label-primary btn-sm loadModalContentBtn" data-bs-target="#dynamicModal" data-modal-file="bank_transaction_reconcile_modal.php?transaction_id='.$transaction['transaction_id'].'" data-bs-toggle="modal">Reconcile</button>';
            }
            $data['table']['body_rows'][] = [
                $transaction['date'],
                numfmt_format_currency($this->currency_format, -1 * $transaction['amount'], 'USD'),
                '<img style="width: 40px; height: 40px;" src="' . $transaction['icon_url'] . '"> <br>' . $transaction['category'],
                $transaction['name'],
                $reconciled
            ];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * API endpoint to email an invoice
     *
     * @param array $data Request data containing invoice_id
     * @return array Response with status message and invoice ID
     * @throws \Exception If invoice_id is missing
     */
    public function emailInvoice($data) {
        $invoiceId = $data['invoice_id'] ?? null;
        if (!$invoiceId) {
            throw new \Exception('Invoice ID is required');
        }

        // Your existing email invoice logic here
        $result = $this->accounting->emailInvoice($invoiceId);
        
        return [
            'message' => 'Invoice emailed successfully',
            'invoice_id' => $invoiceId
        ];
    }

    /**
     * API endpoint to cancel an invoice
     *
     * @param array $data Request data containing invoice_id
     * @return array Response with status message and invoice ID
     * @throws \Exception If invoice_id is missing
     */
    public function cancelInvoice($data) {
        $invoiceId = $data['invoice_id'] ?? null;
        if (!$invoiceId) {
            throw new \Exception('Invoice ID is required');
        }

        $result = $this->accounting->cancelInvoice($invoiceId);
        
        return [
            'message' => 'Invoice cancelled successfully',
            'invoice_id' => $invoiceId
        ];
    }
}
