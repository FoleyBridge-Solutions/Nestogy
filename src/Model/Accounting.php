<?php
// src/Model/Accounting.php

namespace Twetech\Nestogy\Model;

use Twetech\Nestogy\Model\Client;
use PDO;

class Accounting {
    private $pdo;
    private $client;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->client = new Client($pdo);
    }

    public function getInvoices($client_id = false) {
        if ($client_id) {
            $stmt = $this->pdo->prepare("SELECT SQL_CACHE * FROM invoices LEFT JOIN clients ON invoices.invoice_client_id = clients.client_id WHERE invoice_client_id = :client_id ORDER BY invoice_date DESC");
            $stmt->execute(['client_id' => $client_id]);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->pdo->query("SELECT SQL_CACHE * FROM invoices LEFT JOIN clients ON invoices.invoice_client_id = clients.client_id ORDER BY invoice_date DESC");
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $invoices;
    }

    public function getInvoiceTotal($invoice_id) {
        return round($this->getInvoiceAmount($invoice_id), 2);
    }
    public function getQuoteTotal($quote_id) {
        return round($this->getQuoteAmount($quote_id), 2);
    }

    public function getLineItemTotal($item_id) { // Adds up the total for item
        $stmt = $this->pdo->prepare("SELECT * FROM invoice_items WHERE item_id = :item_id");
        $stmt->execute(['item_id' => $item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        $subtotal = $item['item_price'] * $item['item_quantity'] - $item['item_discount'];

        $stmt = $this->pdo->prepare("SELECT * FROM taxes WHERE tax_id = :tax_id");
        $stmt->execute(['tax_id' => $item['item_tax_id']]);
        $tax = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $subtotal + round($subtotal * $tax['tax_percent'] / 100, 2);
        return $total;
    }
    public function getPayments($client_id = false, $sum = false) {
        if ($client_id) {
            if ($sum) {
                $stmt = $this->pdo->prepare("SELECT SUM(payment_amount) AS payment_amount FROM payments WHERE payment_invoice_id IN (SELECT invoice_id FROM invoices WHERE invoice_client_id = :client_id)");
                $stmt->execute(['client_id' => $client_id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE payment_invoice_id IN (SELECT invoice_id FROM invoices WHERE invoice_client_id = :client_id)");
                $stmt->execute(['client_id' => $client_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            if ($sum) {
                $stmt = $this->pdo->query("SELECT SUM(payment_amount) AS payment_amount FROM payments
                    LEFT JOIN invoices ON payments.payment_invoice_id = invoices.invoice_id
                    LEFT JOIN clients ON invoices.invoice_client_id = clients.client_id");
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->pdo->query(
                    "SELECT * FROM payments 
                    LEFT JOIN invoices ON payments.payment_invoice_id = invoices.invoice_id
                    LEFT JOIN clients ON invoices.invoice_client_id = clients.client_id
                    ORDER BY payment_date DESC");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }
    public function getClientBalance($client_id) {
        $stmt = $this->pdo->prepare(
            "
            SELECT SQL_CACHE
                i.invoice_client_id AS client_id,
                SUM(
                    (ii.item_quantity * ii.item_price - ii.item_discount) * (1 + IFNULL(t.tax_percent, 0)/100)
                ) AS total_invoiced,
                IFNULL(total_payments.total_paid, 0) AS total_paid,
                IFNULL(total_payments.total_paid, 0) - SUM(
                    (ii.item_quantity * ii.item_price - ii.item_discount) * (1 + IFNULL(t.tax_percent, 0)/100)
                ) AS client_balance
            FROM 
                invoices i
            LEFT JOIN 
                invoice_items ii ON i.invoice_id = ii.item_invoice_id
            LEFT JOIN 
                taxes t ON ii.item_tax_id = t.tax_id
            LEFT JOIN 
                (
                    SELECT 
                        i2.invoice_client_id,
                        SUM(p.payment_amount) AS total_paid
                    FROM 
                        invoices i2
                    LEFT JOIN 
                        payments p ON i2.invoice_id = p.payment_invoice_id
                    WHERE
                        i2.invoice_client_id = :client_id
                    GROUP BY 
                        i2.invoice_client_id
                ) total_payments ON i.invoice_client_id = total_payments.invoice_client_id
            WHERE
                i.invoice_client_id = :client_id2
            GROUP BY 
                i.invoice_client_id;
            "
        );
        $stmt->execute(['client_id' => $client_id, 'client_id2' => $client_id]);
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC)['client_balance']*-1;
        } else {
            return 0;
        }
    }
    public function getClientPastDueBalance($client_id) {
        $stmt = $this->pdo->prepare(
            "
            SELECT SQL_CACHE
                i.invoice_client_id AS client_id,
                SUM(
                    (ii.item_quantity * ii.item_price - ii.item_discount) * (1 + IFNULL(t.tax_percent, 0)/100)
                ) AS total_invoiced,
                IFNULL(total_payments.total_paid, 0) AS total_paid,
                IFNULL(total_payments.total_paid, 0) - SUM(
                    (ii.item_quantity * ii.item_price - ii.item_discount) * (1 + IFNULL(t.tax_percent, 0)/100)
                ) AS client_balance
            FROM 
                invoices i
            LEFT JOIN 
                invoice_items ii ON i.invoice_id = ii.item_invoice_id
            LEFT JOIN 
                taxes t ON ii.item_tax_id = t.tax_id
            LEFT JOIN 
                (
                    SELECT 
                        i2.invoice_client_id,
                        SUM(p.payment_amount) AS total_paid
                    FROM 
                        invoices i2
                    LEFT JOIN 
                        payments p ON i2.invoice_id = p.payment_invoice_id
                    WHERE
                        i2.invoice_client_id = :client_id
                    GROUP BY 
                        i2.invoice_client_id
                ) total_payments ON i.invoice_client_id = total_payments.invoice_client_id
            WHERE
                i.invoice_client_id = :client_id2
                AND invoice_due <= NOW()
            GROUP BY 
                i.invoice_client_id;
            "
        );
        $stmt->execute(['client_id' => $client_id, 'client_id2' => $client_id]);
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC)['client_balance']*-1;
        } else {
            return 0;
        }
    }
    public function getClientPaidAmount($client_id) {
        // Get the total amount paid by the client during the year
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(payment_amount), 0) AS amount_paid
                FROM payments
            LEFT JOIN invoices
                ON payments.payment_invoice_id = invoices.invoice_id
            WHERE invoice_client_id = :client_id
                AND payment_date >= DATE_FORMAT(NOW(), '%Y-01-01')
        ");
        $stmt->execute(['client_id' => $client_id]);
        $amount_paid = $stmt->fetch();
        return $amount_paid['amount_paid'];
    }
    public function getInvoice($invoice_id) {
        if (!isset($invoice_id)) {
            return false;
        }
        $stmt = $this->pdo->prepare("SELECT SQL_CACHE * FROM invoices
        LEFT JOIN clients ON invoices.invoice_client_id = clients.client_id
        LEFT JOIN contacts ON clients.client_id = contacts.contact_client_id AND contact_primary = 1
        LEFT JOIN locations ON clients.client_id = locations.location_client_id AND location_primary = 1
        WHERE invoice_id = :invoice_id");
        $stmt->execute(['invoice_id' => $invoice_id]);
        $invoice_details = $stmt->fetch(PDO::FETCH_ASSOC);
    
        $stmt = $this->pdo->prepare("SELECT SQL_CACHE * FROM invoice_items
        LEFT JOIN taxes ON invoice_items.item_tax_id = taxes.tax_id
        WHERE item_invoice_id = :invoice_id");
        $stmt->execute(['invoice_id' => $invoice_id]);
        $invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $invoice_details['items'] = $invoice_items;
        $invoice_details['invoice_amount'] = $this->getInvoiceAmount($invoice_id);
        $invoice_details['invoice_balance'] = $this->getInvoiceBalance($invoice_id);
        
        if ($invoice_details['invoice_balance'] < 0.02) {
            $invoice_details['invoice_balance'] = 0;
        }
        return $invoice_details;
    }
    public function getUnbilledTickets($invoice_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM tickets WHERE ticket_invoice_id = :invoice_id AND ticket_invoice_id IS NULL");
        $stmt->execute(['invoice_id' => $invoice_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getTicketsByInvoice($invoice_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM tickets WHERE ticket_invoice_id = :invoice_id");
        $stmt->execute(['invoice_id' => $invoice_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getQuotes($client_id = false) {
        if ($client_id) {
            $stmt = $this->pdo->prepare("SELECT * FROM quotes WHERE quote_client_id = :client_id ORDER BY quote_date DESC");
            $stmt->execute(['client_id' => $client_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM quotes ORDER BY quote_date DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    public function getQuote($quote_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM quotes WHERE quote_id = :quote_id");
        $stmt->execute(['quote_id' => $quote_id]);
        $quote_details = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("SELECT * FROM invoice_items WHERE item_quote_id = :quote_id");
        $stmt->execute(['quote_id' => $quote_id]);
        $quote_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $quote_details['items'] = $quote_items;

        return $quote_details;
    }
    public function getSubscriptions($client_id = false) {
        if ($client_id) {
            $stmt = $this->pdo->prepare("SELECT * FROM subscriptions 
            LEFT JOIN products ON subscriptions.subscription_product_id = products.product_id
            WHERE subscription_client_id = :client_id");
            $stmt->execute(['client_id' => $client_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM subscriptions LEFT JOIN products ON subscriptions.subscription_product_id = products.product_id");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    public function getSubscription($subscription_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM subscriptions WHERE subscription_id = :subscription_id");
        $stmt->execute(['subscription_id' => $subscription_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getPayment($payment_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM payments LEFT JOIN clients ON payments.payment_client_id = clients.client_id WHERE payment_id = :payment_id");
        $stmt->execute(['payment_id' => $payment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getPaymentsByReference($reference) {
        $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE payment_reference = :reference");
        $stmt->execute(['reference' => $reference]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getPaymentCategories() {
        $stmt = $this->pdo->query("SELECT * FROM categories WHERE category_type = 'Payment Method' AND category_archived_at IS NULL ORDER BY category_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getPaymentAccounts() {
        $stmt = $this->pdo->query("SELECT * FROM accounts LEFT JOIN account_types ON accounts.account_type = account_types.account_type_id WHERE accounts.account_archived_at IS NULL ORDER BY accounts.account_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getProducts() {
        $stmt = $this->pdo->query("SELECT * FROM products WHERE product_archived_at IS NULL ORDER BY product_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getProductsAutocomplete() {
        $stmt = $this->pdo->query("SELECT product_name AS label, product_description AS description, product_price AS price, product_tax_id AS tax, product_id AS productId FROM products WHERE product_archived_at IS NULL");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getProduct($product_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE product_id = :product_id");
        $stmt->execute(['product_id' => $product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getTaxes() {
        $stmt = $this->pdo->query("SELECT * FROM taxes WHERE tax_archived_at IS NULL ORDER BY tax_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getCategories($type = 'Income') {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE category_archived_at IS NULL AND category_type = :category_type ORDER BY category_name ASC");
        $stmt->execute(['category_type' => $type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getInvoiceAmount($invoice_id) {
        $stmt = $this->pdo->prepare("SELECT SQL_CACHE
            i.invoice_client_id AS client_id,
            SUM(
                (ii.item_quantity * ii.item_price - ii.item_discount) * (1 + IFNULL(t.tax_percent, 0)/100)
            ) AS total_invoiced,
            IFNULL(total_payments.total_paid, 0) AS total_paid
        FROM invoices i
        LEFT JOIN invoice_items ii ON i.invoice_id = ii.item_invoice_id
        LEFT JOIN taxes t ON ii.item_tax_id = t.tax_id
        LEFT JOIN (
            SELECT i2.invoice_client_id, SUM(p.payment_amount) AS total_paid
            FROM invoices i2
            LEFT JOIN payments p ON i2.invoice_id = p.payment_invoice_id
            WHERE i2.invoice_id = :invoice_id2
        ) total_payments ON i.invoice_client_id = total_payments.invoice_client_id
        WHERE i.invoice_id = :invoice_id");
        $stmt->execute(['invoice_id' => $invoice_id, 'invoice_id2' => $invoice_id]);
        return round($stmt->fetch(PDO::FETCH_ASSOC)['total_invoiced'], 2);
    }
    public function getQuoteAmount($quote_id) {
        $stmt = $this->pdo->prepare("SELECT SQL_CACHE
            SUM(
                (ii.item_quantity * ii.item_price - ii.item_discount) * (1 + IFNULL(t.tax_percent, 0)/100)
            ) AS total_quoted
        FROM quotes q
        LEFT JOIN invoice_items ii ON q.quote_id = ii.item_quote_id
        LEFT JOIN taxes t ON ii.item_tax_id = t.tax_id
        WHERE q.quote_id = :quote_id");
        $stmt->execute(['quote_id' => $quote_id]);
        return round($stmt->fetch(PDO::FETCH_ASSOC)['total_quoted'], 2);
    }
    public function getPaymentsByInvoice($invoice_id, $sum = false) {
        if ($sum) {
            $stmt = $this->pdo->prepare("SELECT SQL_CACHE SUM(payment_amount) AS total_paid FROM payments WHERE payment_invoice_id = :invoice_id");
            $stmt->execute(['invoice_id' => $invoice_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC)['total_paid'];
        } else {
            $stmt = $this->pdo->prepare("SELECT SQL_CACHE * FROM payments WHERE payment_invoice_id = :invoice_id");
            $stmt->execute(['invoice_id' => $invoice_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    }
    public function getInvoiceBalance($invoice_id) {
        return $this->getInvoiceAmount($invoice_id) - $this->getPaymentsByInvoice($invoice_id, true);
    }
    private function calculateTaxOwed($result) {
        $monthly_fractional_payment = array_fill(1, 12, []);
        $monthly_tax_owed = array_fill(1, 12, []);

        foreach ($result as $row) {
            $month = date('n', strtotime($row['payment_date']));
            $tax_name = $row['tax_name'];
        
            $invoice_amount = $row['invoice_amount'];
            $payment_amount = $row['payment_amount'];
            $percent_paid = $invoice_amount > 0 ? $payment_amount / $invoice_amount : 0;
            $item_price = $row['item_price'];
            $item_quantity = $row['item_quantity'];
            $item_discount = $row['item_discount'];
            $item_total = ($item_price * $item_quantity) - $item_discount;
            $fractional_payment_amount = $item_total * $percent_paid;
            $tax_rate = $row['tax_percent'];
            $tax_owed = $fractional_payment_amount * $tax_rate / 100;
        
            $total_fractional_payment = isset($monthly_fractional_payment[$month][$tax_name]) ? $monthly_fractional_payment[$month][$tax_name] : 0;
            $total_tax_owed = isset($monthly_tax_owed[$month][$tax_name]) ? $monthly_tax_owed[$month][$tax_name] : 0;
        
            $monthly_fractional_payment[$month][$tax_name] = $total_fractional_payment + $fractional_payment_amount;
            $monthly_tax_owed[$month][$tax_name] = $total_tax_owed + $tax_owed;
        }

        return [
            'monthly_fractional_payment' => $monthly_fractional_payment,
            'monthly_tax_owed' => $monthly_tax_owed,
        ];
    }
    public function getTaxReport($year = false, $month = false) {
        if (!$year) {
            $year = date('Y');
        }
        if (!$month) {
            $month = date('n');
        }
        
        $sql = "
            SELECT * FROM payments
            LEFT JOIN invoices ON payments.payment_invoice_id = invoices.invoice_id
            LEFT JOIN invoice_items ON invoices.invoice_id = invoice_items.item_invoice_id
            LEFT JOIN taxes ON invoice_items.item_tax_id = taxes.tax_id
            LEFT JOIN clients ON invoices.invoice_client_id = clients.client_id
            WHERE YEAR(payments.payment_date) = :year
            ORDER BY taxes.tax_name, MONTH(payments.payment_date), taxes.tax_id, clients.client_name, invoices.invoice_id, payments.payment_id, invoice_items.item_id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['year' => $year]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->calculateTaxOwed($result);
    }
    public function getCollectionsReport() {
        $stmt = $this->pdo->query("SELECT * FROM clients
        ORDER BY client_name desc");
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($clients as $client) {
            $client['balance'] = $this->getClientBalance($client['client_id']);
            $client['monthly_recurring_amount'] = $this->getMonthlySubscriptionAmount($client['client_id']);
            $client['past_due_amount'] = $this->getPastDueAmount($client['client_id']);
            //Get billing contact phone
            $client['contact_phone'] = $this->client->getClientContact($client['client_id'], 'billing')['contact_phone'] ?? $this->client->getClientContact($client['client_id'])['contact_phone'] ?? $this->client->getClientContact($client['client_id'])['contact_mobile'];

            //Save changes to array
            $data_clients[] = $client;
        }

        $data['collections_report']['clients'] = $data_clients;
        $data['past_due_filter'] = 2;
        return $data;
    }
    public function getMonthlySubscriptionAmount($client_id) {
        $stmt = $this->pdo->prepare("
            SELECT SQL_CACHE SUM(products.product_price * subscriptions.subscription_product_quantity) AS monthly_amount
            FROM subscriptions
            LEFT JOIN products ON subscriptions.subscription_product_id = products.product_id
            WHERE subscription_client_id = :client_id
        ");
        $stmt->execute(['client_id' => $client_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['monthly_amount'] ?? 0;
    }
    public function getPastDueAmount($client_id) {
        return 0;
    }
    public function getAllClientData() {
        $sql = "
            SELECT 
                clients.client_id,
                clients.client_name,
                COALESCE(SUM(invoices.invoice_amount), 0) AS client_balance,
                COALESCE(SUM(payments.payment_amount), 0) AS client_payments,
                COALESCE(SUM(subscriptions.subscription_product_quantity * products.product_price), 0) AS client_recurring_monthly
            FROM clients
            LEFT JOIN invoices ON clients.client_id = invoices.invoice_client_id
            LEFT JOIN payments ON invoices.invoice_id = payments.payment_invoice_id
            LEFT JOIN subscriptions ON clients.client_id = subscriptions.subscription_client_id
            LEFT JOIN products ON subscriptions.subscription_product_id = products.product_id
            GROUP BY clients.client_id
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getStatement($client_id) {

        $client_id = intval($_GET['client_id']);

        $sql_client_details = "
        SELECT
            client_name,
            client_type,
            client_website,
            client_net_terms
        FROM
            clients
        WHERE
            client_id = :client_id
        ";

        $stmt = $this->pdo->prepare($sql_client_details);
        $stmt->execute(['client_id' => $client_id]);
        $row_client_details = $stmt->fetch(PDO::FETCH_ASSOC);
    
        $client_name = nullable_htmlentities($row_client_details['client_name']);
        $client_type = nullable_htmlentities($row_client_details['client_type']);
        $client_website = nullable_htmlentities($row_client_details['client_website']);
        $client_net_terms = intval($row_client_details['client_net_terms']);
    
        $client_invoices = $this->getInvoices($client_id);

        if (isset($_GET['max_rows'])) {
            $outstanding_wording = strval($_GET['max_rows']) . " Most Recent";
        } else {
            $outstanding_wording = "Outstanding";
        }

        $sql_client_transactions = "SELECT * FROM invoices 
                                    WHERE invoices.invoice_client_id = :client_id
                                    AND invoices.invoice_status NOT LIKE 'Draft'
                                    AND invoices.invoice_status NOT LIKE 'Cancelled'
                                    ORDER BY invoices.invoice_date DESC
                                    LIMIT 50
                                    ";

        $stmt = $this->pdo->prepare($sql_client_transactions);
        $stmt->execute(['client_id' => $client_id]);
        $result_client_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result_client_transactions as $key => $row) {
            $result_client_transactions[$key]['invoice_amount'] = $this->getInvoiceAmount($row['invoice_id'])['invoice_amount'];
            $result_client_transactions[$key]['invoice_balance'] = $this->getInvoiceBalance($row['invoice_id']);
            $result_client_transactions[$key]['payments'] = $this->getPaymentsByInvoice($row['invoice_id']);
        }

        return [
            'client_name' => $client_name,
            'client_id' => $client_id,
            'client_type' => $client_type,
            'client_website' => $client_website,
            'client_net_terms' => $client_net_terms,
            'client_balance' => $this->getClientBalance($client_id),
            'client_past_due_amount' => $this->getPastDueAmount($client_id),
            'outstanding_wording' => $outstanding_wording,
            'transactions' => $result_client_transactions,
            'unpaid_invoices' => $client_invoices,
            'ageing_balance' => $this->getClientAgeingBalance($client_id, 0, 30),
            'ageing_balance_30' => $this->getClientAgeingBalance($client_id, 30, 60),
            'ageing_balance_60' => $this->getClientAgeingBalance($client_id, 60, 90),
            'ageing_balance_90' => $this->getClientAgeingBalance($client_id, 90, null),
        ];


    }
    public function getClientAgeingBalance($client_id, $from, $to) {
        $client_id = intval($client_id);
        $from = intval($from);
        $to = intval($to);

        if ($to == null) {
            //If to is null, set it to the first day in the database
            $to = date('Y-m-d', strtotime('2000-01-01'));
        }

        // Get from and to dates for the ageing balance by subtracting the number of days from the current date
        $from_date = date('Y-m-d', strtotime('-' . $from . ' days'));
        $to_date = date('Y-m-d', strtotime('-' . $to . ' days'));
    
        //Get all invoice ids that are not draft or cancelled from the date range
        $sql = "SELECT invoice_id FROM invoices
        WHERE invoice_client_id = $client_id
        AND invoice_status NOT LIKE 'Draft'
        AND invoice_status NOT LIKE 'Cancelled'
        AND invoice_date <= :from_date
        AND invoice_date >= :to_date";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['from_date' => $from_date, 'to_date' => $to_date]);
        $result_invoice_ids = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $invoice_ids = [];
        foreach ($result_invoice_ids as $row) {
            $invoice_ids[] = $row['invoice_id'];
        }
    
        // Get Balance for the invoices in the date range
        $balance = 0;
        foreach ($invoice_ids as $invoice_id) {
            $balance += $this->getInvoiceBalance($invoice_id);
            error_log("Balance for invoice_id " . $invoice_id . " is " . $this->getInvoiceBalance($invoice_id));
        }
        error_log("Balance for client_id " . $client_id . " from " . $from_date . " to " . $to_date . " is " . $balance);
        return $balance;
    }
    public function getRecievables($month, $year) {
        $start_day = date('Y-m-01', strtotime($year . '-' . $month . '-01'));
        $end_day = date('Y-m-t', strtotime($year . '-' . $month . '-01'));

        $stmt = $this->pdo->prepare("SELECT invoice_id FROM invoices WHERE invoice_created_at >= :start_day AND invoice_created_at <= :end_day");
        $stmt->execute(['start_day' => $start_day, 'end_day' => $end_day]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_invoices = 0;

        foreach ($invoices as $invoice) {
            $total_invoices += $this->getInvoiceBalance($invoice['invoice_id']);
        }

        return $total_invoices;
    }
    public function getIncome($month, $year) {
        $start_day = date('Y-m-01', strtotime($year . '-' . $month . '-01'));
        $end_day = date('Y-m-t', strtotime($year . '-' . $month . '-01'));

        $stmt = $this->pdo->prepare("SELECT payment_amount FROM payments WHERE payment_created_at >= :start_day AND payment_created_at <= :end_day");
        $stmt->execute(['start_day' => $start_day, 'end_day' => $end_day]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_payments = 0;

        foreach ($payments as $payment) {
            $total_payments += $payment['payment_amount'];
        }

        return $total_payments;
    }
    public function getExpenses($month, $year) {
        $start_day = date('Y-m-01', strtotime($year . '-' . $month . '-01'));
        $end_day = date('Y-m-t', strtotime($year . '-' . $month . '-01'));

        $stmt = $this->pdo->prepare("SELECT expense_amount FROM expenses WHERE expense_created_at >= :start_day AND expense_created_at <= :end_day");
        $stmt->execute(['start_day' => $start_day, 'end_day' => $end_day]);
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_expenses = 0;

        foreach ($expenses as $expense) {
            $total_expenses += $expense['expense_amount'];
        }

        return $total_expenses;
    }
    public function getProfit($month, $year) {
        return $this->getIncome($month, $year) - $this->getExpenses($month, $year);
    }

    public function getAllUnbilledTickets($month, $year) {
        $start_day = date('Y-m-01', strtotime($year . '-' . $month . '-01'));
        $end_day = date('Y-m-t', strtotime($year . '-' . $month . '-01'));

        $stmt = $this->pdo->prepare("SELECT ticket_id FROM tickets WHERE
            ticket_created_at >= :start_day AND ticket_created_at <= :end_day
            AND ticket_invoice_id = 0
            AND ticket_billable = 1
        ");
        $stmt->execute(['start_day' => $start_day, 'end_day' => $end_day]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return count($tickets);
    }
    public function getTotalQuotes($month, $year) {
        
        $start_day = date('Y-m-01', strtotime($year . '-' . $month . '-01'));
        $end_day = date('Y-m-t', strtotime($year . '-' . $month . '-01'));

        $stmt = $this->pdo->prepare("SELECT quote_id FROM quotes WHERE quote_created_at >= :start_day AND quote_created_at <= :end_day");
        $stmt->execute(['start_day' => $start_day, 'end_day' => $end_day]);
        $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_quotes = 0;

        foreach ($quotes as $quote) {
            $total_quotes += $this->getQuoteAmount($quote['quote_id']);
        }

        return $total_quotes;
    }
    public function getTotalQuotesAccepted($month, $year) {
        $start_day = date('Y-m-01', strtotime($year . '-' . $month . '-01'));
        $end_day = date('Y-m-t', strtotime($year . '-' . $month . '-01'));
        
        $stmt = $this->pdo->prepare("SELECT quote_id FROM quotes WHERE quote_status = 'Accepted' AND quote_created_at >= :start_day AND quote_created_at <= :end_day");
        $stmt->execute(['start_day' => $start_day, 'end_day' => $end_day]);
        $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_quotes = 0;

        foreach ($quotes as $quote) {
            $total_quotes += $this->getQuoteAmount($quote['quote_id']);
        }

        return $total_quotes;
    }
    private function polynomialRegression(array $x, array $y, int $degree) {
        $matrix = [];
        $vector = [];

        for ($i = 0; $i <= $degree; $i++) {
            for ($j = 0; $j <= $degree; $j++) {
                $matrix[$i][$j] = array_sum(array_map(function($xi) use ($i, $j) {
                    return pow($xi, $i + $j);
                }, $x));
            }
            $vector[$i] = array_sum(array_map(function($xi, $yi) use ($i) {
                return $yi * pow($xi, $i);
            }, $x, $y));
        }

        return $this->solveLinearSystem($matrix, $vector);
    }

    private function solveLinearSystem(array $matrix, array $vector) {
        $n = count($vector);
        for ($i = 0; $i < $n; $i++) {
            $maxEl = abs($matrix[$i][$i]);
            $maxRow = $i;
            for ($k = $i + 1; $k < $n; $k++) {
                if (abs($matrix[$k][$i]) > $maxEl) {
                    $maxEl = abs($matrix[$k][$i]);
                    $maxRow = $k;
                }
            }

            for ($k = $i; $k < $n; $k++) {
                $tmp = $matrix[$maxRow][$k];
                $matrix[$maxRow][$k] = $matrix[$i][$k];
                $matrix[$i][$k] = $tmp;
            }
            $tmp = $vector[$maxRow];
            $vector[$maxRow] = $vector[$i];
            $vector[$i] = $tmp;

            for ($k = $i + 1; $k < $n; $k++) {
                $c = -$matrix[$k][$i] / $matrix[$i][$i];
                for ($j = $i; $j < $n; $j++) {
                    if ($i == $j) {
                        $matrix[$k][$j] = 0;
                    } else {
                        $matrix[$k][$j] += $c * $matrix[$i][$j];
                    }
                }
                $vector[$k] += $c * $vector[$i];
            }
        }

        $solution = array_fill(0, $n, 0);
        for ($i = $n - 1; $i >= 0; $i--) {
            $solution[$i] = $vector[$i] / $matrix[$i][$i];
            for ($k = $i - 1; $k >= 0; $k--) {
                $vector[$k] -= $matrix[$k][$i] * $solution[$i];
            }
        }

        return $solution;
    }

    public function getEstimatedProfit($month, $year) {
        // Collect data for the past 24 months
        $data = [];
        for ($i = 1; $i <= 24; $i++) {
            $date = strtotime("-$i month", strtotime("$year-$month-01"));
            $pastMonth = date('m', $date);
            $pastYear = date('Y', $date);
            $profit = $this->getProfit($pastMonth, $pastYear);
            $data[] = [$i, $profit];
        }

        // Prepare data for regression
        $samples = array_column($data, 0); // Time (months)
        $targets = array_column($data, 1); // Profits

        // Perform polynomial regression
        $coefficients = $this->polynomialRegression($samples, $targets, 2); // Degree 2 polynomial regression

        // Predict profit for the given month and year
        $futureMonthIndex = 0; // Current month
        $predictedProfit = 0;
        for ($i = 0; $i < count($coefficients); $i++) {
            $predictedProfit += $coefficients[$i] * pow($futureMonthIndex, $i);
        }

        // Adjust for seasonality using last year's profit for the same month
        $lastYearProfit = $this->getProfit($month, $year - 1);
        if ($lastYearProfit > 0) {
            $predictedProfit = ($predictedProfit + $lastYearProfit) / 2;
        }

        return $predictedProfit;
    }

    public function getRecievablesByCategory($month, $year, $category) {
        $start_day = date('Y-m-01', strtotime($year . '-' . $month . '-01'));
        $end_day = date('Y-m-t', strtotime($year . '-' . $month . '-01'));

        $stmt = $this->pdo->prepare("SELECT SUM(invoice_amount) AS total_recievables FROM invoices WHERE invoice_created_at >= :start_day AND invoice_created_at <= :end_day AND invoice_category = :category");
        $stmt->execute(['start_day' => $start_day, 'end_day' => $end_day, 'category' => $category]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $invoices;
    }
}