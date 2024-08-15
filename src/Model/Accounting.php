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
            $stmt = $this->pdo->prepare("SELECT * FROM invoices WHERE invoice_client_id = :client_id ORDER BY invoice_date DESC");
            $stmt->execute(['client_id' => $client_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM invoices ORDER BY invoice_date DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    public function getPayments($client_id = false) {
        if ($client_id) {
            $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE payment_invoice_id IN (SELECT invoice_id FROM invoices WHERE invoice_client_id = :client_id)");
            $stmt->execute(['client_id' => $client_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->pdo->query(
                "SELECT * FROM payments 
                LEFT JOIN invoices ON payments.payment_invoice_id = invoices.invoice_id
                LEFT JOIN clients ON invoices.invoice_client_id = clients.client_id
                ORDER BY payment_date DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    public function getClientBalance($client_id) {
        $invoices = $this->getInvoices($client_id);
        $payments = $this->getPayments($client_id);

        $balance = 0;
        foreach ($invoices as $invoice) {
            $balance += $invoice['invoice_amount'];
        }
        foreach ($payments as $payment) {
            $balance -= $payment['payment_amount'];
        }
        return $balance;
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
        $stmt = $this->pdo->prepare("SELECT * FROM invoices WHERE invoice_id = :invoice_id");
        $stmt->execute(['invoice_id' => $invoice_id]);
        $invoice_details = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("SELECT * FROM invoice_items WHERE item_invoice_id = :invoice_id");
        $stmt->execute(['invoice_id' => $invoice_id]);
        $invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        $invoice_details['items'] = $invoice_items;
    
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
    public function getInvoiceBalance($invoice_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM invoices WHERE invoice_id = :invoice_id");
        $stmt->execute(['invoice_id' => $invoice_id]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        $invoice_amount = $invoice['invoice_amount'];

        $payments = $this->getPaymentsByInvoice($invoice_id);
        $payments_amount = array_sum(array_column($payments, 'payment_amount'));
        return $invoice_amount - $payments_amount;
    }
    public function getPaymentsByInvoice($invoice_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE payment_invoice_id = :invoice_id");
        $stmt->execute(['invoice_id' => $invoice_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $stmt = $this->pdo->prepare("SELECT * FROM subscriptions LEFT JOIN products ON subscriptions.subscription_product_id = products.product_id WHERE subscription_client_id = :client_id");
        $stmt->execute(['client_id' => $client_id]);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $monthly_amount = 0;
        foreach ($subscriptions as $subscription) {
            $product_price = $subscription['product_price'];
            $subscription_product_quantity = $subscription['subscription_product_quantity'];
            $monthly_amount += $product_price * $subscription_product_quantity;
        }
        return $monthly_amount;       
    }
    public function getPastDueAmount($client_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM invoices WHERE invoice_client_id = :client_id AND invoice_due < NOW()");
        $stmt->execute(['client_id' => $client_id]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $past_due_amount = 0;
        foreach ($invoices as $invoice) {
            $invoice_amount = $invoice['invoice_amount'];
            $payments = $this->getPaymentsByInvoice($invoice['invoice_id']);
            $payments_amount = array_sum(array_column($payments, 'payment_amount'));
            $past_due_amount += $invoice_amount - $payments_amount;
        }
        return $past_due_amount;
    }
}