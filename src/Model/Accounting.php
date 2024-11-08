<?php
// src/Model/Accounting.php

namespace Twetech\Nestogy\Model;

use Twetech\Nestogy\Model\Client;
use Redis;
use PDO;

/**
 * Class Accounting
 * 
 * Handles all accounting-related operations including invoices, payments, and financial calculations
 * 
 * @package Twetech\Nestogy\Model
 */
class Accounting {
    /** @var PDO */
    private $pdo;
    
    /** @var Client */
    private $client;
    
    /** @var Redis */
    private $redis;

    /**
     * Constructor for Accounting class
     * 
     * @param PDO $pdo Database connection instance
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->client = new Client($pdo);
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }

    /**
     * Retrieves cached invoice totals
     * 
     * @param int $invoice_id The ID of the invoice
     * @param bool $useCache Whether to use cached data
     * @return array|null Returns cached totals or null if not found/expired
     */
    private function getCachedInvoiceTotals($invoice_id, $useCache = true) {
        if (!$useCache) {
            return null;
        }

        $cachedData = $this->redis->get("invoice_totals:$invoice_id");
        if ($cachedData) {
            $cachedData = json_decode($cachedData, true);
            if (time() - $cachedData['timestamp'] < 900) { // 15 minutes cache
                return $cachedData['totals'];
            }
        }

        return null;
    }

    /**
     * Sets cached invoice totals
     * 
     * @param int $invoice_id The ID of the invoice
     * @param array $totals Array of total values to cache
     * @param bool $merge Whether to merge with existing cached data
     * @return void
     */
    private function setCachedInvoiceTotals($invoice_id, $totals, $merge = false) {
        $key = "invoice_totals:$invoice_id";
        if ($merge) {
            $existingData = $this->redis->get($key);
            if ($existingData) {
                $existingData = json_decode($existingData, true);
                $totals = array_merge($existingData['totals'], $totals);
            }
        }

        $cacheData = [
            'totals' => $totals,
            'timestamp' => time()
        ];

        $this->redis->set($key, json_encode($cacheData));
        $this->redis->expire($key, 300); // Set expiration to 5 minutes
    }

    /**
     * Retrieves invoices for a specific client or all clients
     * 
     * @param int|false $client_id Client ID or false for all clients
     * @return array Array of invoice records
     */
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

    /**
     * Retrieves aging (overdue) invoices for a client or all clients
     * 
     * @param int|false $client_id Client ID or false for all clients
     * @return array Array of aging invoice records
     */
    public function getAgingInvoices($client_id = false) {
        if ($client_id) {
            $stmt = $this->pdo->prepare("SELECT * FROM invoices
            LEFT JOIN clients ON invoices.invoice_client_id = clients.client_id
            WHERE invoice_client_id = :client_id
            AND invoice_due < NOW()
            AND invoice_status NOT IN ('Paid', 'Cancelled')
            ");
            $stmt->execute(['client_id' => $client_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM invoices
            LEFT JOIN clients ON invoices.invoice_client_id = clients.client_id
            WHERE invoice_due < NOW()
            AND invoice_status NOT IN ('Paid', 'Cancelled')");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Gets the total amount for an invoice
     * 
     * @param int $invoice_id Invoice ID
     * @return float Rounded invoice total
     */
    public function getInvoiceTotal($invoice_id) {
        return round($this->getInvoiceAmount($invoice_id), 2);
    }

    /**
     * Gets the total amount for a quote
     * 
     * @param int $quote_id Quote ID
     * @return float Rounded quote total
     */
    public function getQuoteTotal($quote_id) {
        return round($this->getQuoteAmount($quote_id), 2);
    }

    /**
     * Calculates the total for a line item including tax
     * 
     * @param int $item_id Item ID
     * @return float Total amount for the item
     */
    public function getLineItemTotal($item_id) {
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

    /**
     * Retrieves payments for a client or all clients
     * 
     * @param int|false $client_id Client ID or false for all clients
     * @param bool $sum Whether to return sum of payments instead of details
     * @return array Payment records or sum
     */
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

    /**
     * Gets the current balance for a client
     * 
     * @param int $client_id Client ID
     * @return float Client balance
     */
    public function getClientBalance($client_id) {
        return $this->getClientagingBalance($client_id, 0, 99999);
    }

    /**
     * Gets the past due balance for a client
     * 
     * @param int $client_id Client ID
     * @return float Past due balance
     */
    public function getClientPastDueBalance($client_id) {
        return $this->getClientagingBalance($client_id, 31, 99999);
    }

    /**
     * Calculates total amount paid by client in current year
     * 
     * @param int $client_id Client ID
     * @return float Total amount paid
     */
    public function getClientPaidAmount($client_id) {
        // Get the total amount paid by the client during the year
        $stmt = $this->pdo->prepare(
            "SELECT SQL_CACHE COALESCE(SUM(payment_amount), 0) AS amount_paid
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

    /**
     * Retrieves detailed invoice information including items and balances
     * 
     * @param int $invoice_id Invoice ID
     * @return array|false Invoice details or false if not found
     */
    public function getInvoice($invoice_id) {
        if (!isset($invoice_id)) {
            return false;
        }
        $stmt = $this->pdo->prepare("SELECT  * FROM invoices
        LEFT JOIN clients ON invoices.invoice_client_id = clients.client_id
        LEFT JOIN contacts ON clients.client_id = contacts.contact_client_id AND contact_primary = 1
        LEFT JOIN locations ON clients.client_id = locations.location_client_id AND location_primary = 1
        WHERE invoice_id = :invoice_id");
        $stmt->execute(['invoice_id' => $invoice_id]);
        $invoice_details = $stmt->fetch(PDO::FETCH_ASSOC);
    
        $stmt = $this->pdo->prepare("SELECT  * FROM invoice_items
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

    /**
     * Gets unbilled tickets associated with an invoice
     * 
     * @param int $invoice_id Invoice ID
     * @return array Array of unbilled tickets
     */
    public function getUnbilledTickets($invoice_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM tickets WHERE ticket_invoice_id = :invoice_id AND ticket_invoice_id IS NULL");
        $stmt->execute(['invoice_id' => $invoice_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets all tickets associated with an invoice
     * 
     * @param int $invoice_id Invoice ID
     * @return array Array of tickets
     */
    public function getTicketsByInvoice($invoice_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM tickets WHERE ticket_invoice_id = :invoice_id");
        $stmt->execute(['invoice_id' => $invoice_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves quotes for a client or all clients
     * 
     * @param int|false $client_id Client ID or false for all clients
     * @return array Array of quotes
     */
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

    /**
     * Gets detailed quote information including items
     * 
     * @param int $quote_id Quote ID
     * @return array Quote details
     */
    public function getQuote($quote_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM quotes WHERE quote_id = :quote_id");
        $stmt->execute(['quote_id' => $quote_id]);
        $quote_details = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("SELECT * FROM invoice_items LEFT JOIN taxes ON invoice_items.item_tax_id = taxes.tax_id WHERE item_quote_id = :quote_id");
        $stmt->execute(['quote_id' => $quote_id]);
        $quote_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $quote_details['items'] = $quote_items;

        return $quote_details;
    }

    /**
     * Retrieves subscriptions for a client or all clients
     * 
     * @param int|false $client_id Client ID or false for all clients
     * @return array Array of subscriptions
     */
    public function getSubscriptions($client_id = false) {
        if ($client_id) {
            $stmt = $this->pdo->prepare("SELECT *,
            (products.product_price * subscriptions.subscription_product_quantity) AS subscription_subtotal,
            (products.product_price * subscriptions.subscription_product_quantity) * (1 + IFNULL(taxes.tax_percent, 0)/100) AS subscription_total
            FROM subscriptions 
            LEFT JOIN products ON subscriptions.subscription_product_id = products.product_id
            LEFT JOIN clients ON subscriptions.subscription_client_id = clients.client_id
            LEFT JOIN taxes ON products.product_tax_id = taxes.tax_id
            WHERE subscription_client_id = :client_id");
            $stmt->execute(['client_id' => $client_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->pdo->query("SELECT *,
            (products.product_price * subscriptions.subscription_product_quantity) AS subscription_subtotal,
            (products.product_price * subscriptions.subscription_product_quantity) * (1 + IFNULL(taxes.tax_percent, 0)/100) AS subscription_total
            FROM subscriptions
            LEFT JOIN products ON subscriptions.subscription_product_id = products.product_id
            LEFT JOIN clients ON subscriptions.subscription_client_id = clients.client_id
            LEFT JOIN taxes ON products.product_tax_id = taxes.tax_id");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Gets detailed subscription information
     * 
     * @param int $subscription_id Subscription ID
     * @return array Subscription details
     */
    public function getSubscription($subscription_id) {
        $stmt = $this->pdo->prepare("SELECT *,
        (products.product_price * subscriptions.subscription_product_quantity) AS subscription_subtotal,
        (products.product_price * subscriptions.subscription_product_quantity) * (1 + IFNULL(taxes.tax_percent, 0)/100) AS subscription_total
        FROM subscriptions 
        LEFT JOIN products ON subscriptions.subscription_product_id = products.product_id
        LEFT JOIN clients ON subscriptions.subscription_client_id = clients.client_id
        LEFT JOIN taxes ON products.product_tax_id = taxes.tax_id
        WHERE subscription_id = :subscription_id");
        $stmt->execute(['subscription_id' => $subscription_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves payment information
     * 
     * @param int $payment_id Payment ID
     * @return array Payment details
     */
    public function getPayment($payment_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM payments LEFT JOIN clients ON payments.payment_client_id = clients.client_id WHERE payment_id = :payment_id");
        $stmt->execute(['payment_id' => $payment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Gets payments by reference number
     * 
     * @param string $reference Payment reference
     * @return array Array of payments
     */
    public function getPaymentsByReference($reference) {
        $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE payment_reference = :reference");
        $stmt->execute(['reference' => $reference]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all payments for a client
     * 
     * @param int $client_id Client ID
     * @return array Array of payments
     */
    public function getPaymentsByClient($client_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM payments LEFT JOIN invoices ON payments.payment_invoice_id = invoices.invoice_id WHERE invoices.invoice_client_id = :client_id");
        $stmt->execute(['client_id' => $client_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets the most recent payment for a client
     * 
     * @param int $client_id Client ID
     * @return array|false Last payment details or false if none
     */
    public function getLastPaymentByClient($client_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM payments LEFT JOIN invoices ON payments.payment_invoice_id = invoices.invoice_id WHERE invoices.invoice_client_id = :client_id ORDER BY payment_date DESC LIMIT 1");
        $stmt->execute(['client_id' => $client_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all payment categories
     * 
     * @return array Array of payment categories
     */
    public function getPaymentCategories() {
        $stmt = $this->pdo->query("SELECT * FROM categories WHERE category_type = 'Payment Method' AND category_archived_at IS NULL ORDER BY category_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets all payment accounts
     * 
     * @return array Array of payment accounts
     */
    public function getPaymentAccounts() {
        $stmt = $this->pdo->query("SELECT * FROM accounts LEFT JOIN account_types ON accounts.account_type = account_types.account_type_id WHERE accounts.account_archived_at IS NULL ORDER BY accounts.account_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all active products
     * 
     * @return array Array of products
     */
    public function getProducts() {
        $stmt = $this->pdo->query("SELECT * FROM products WHERE product_archived_at IS NULL ORDER BY product_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets products formatted for autocomplete
     * 
     * @return array Array of products with autocomplete formatting
     */
    public function getProductsAutocomplete() {
        $stmt = $this->pdo->query("SELECT product_name AS label, product_description AS description, product_price AS price, product_tax_id AS tax, product_id AS productId FROM products WHERE product_archived_at IS NULL");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves product details
     * 
     * @param int $product_id Product ID
     * @return array Product details
     */
    public function getProduct($product_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE product_id = :product_id");
        $stmt->execute(['product_id' => $product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Gets all active tax rates
     * 
     * @return array Array of tax rates
     */
    public function getTaxes() {
        $stmt = $this->pdo->query("SELECT * FROM taxes WHERE tax_archived_at IS NULL ORDER BY tax_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves categories of specified type
     * 
     * @param string $type Category type
     * @return array Array of categories
     */
    public function getCategories($type = 'Income') {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE category_archived_at IS NULL AND category_type = :category_type ORDER BY category_name ASC");
        $stmt->execute(['category_type' => $type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculates the total amount for a quote
     * 
     * @param int $quote_id Quote ID
     * @return float Rounded quote amount
     */
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

    /**
     * Calculates invoice amount based on type
     * 
     * @param int $invoice_id Invoice ID
     * @param string $type Type of amount (total, subtotal, tax)
     * @param bool $useCache Whether to use cached values
     * @return float Calculated amount
     */
    public function getInvoiceAmount($invoice_id, $type = 'total', $useCache = true) {
        $cachedTotals = $this->getCachedInvoiceTotals($invoice_id, $useCache);
        if ($cachedTotals !== null && isset($cachedTotals[$type])) {
            return $cachedTotals[$type];
        }

        // Calculate the invoice amount
        if ($type == 'total') {
            $stmt = $this->pdo->prepare("SELECT SQL_CACHE
                i.invoice_discount_amount,
                SUM((ii.item_quantity * ii.item_price - ii.item_discount) * (1 + IFNULL(t.tax_percent, 0)/100)) AS total_invoiced
            FROM invoices i
            LEFT JOIN invoice_items ii ON i.invoice_id = ii.item_invoice_id
            LEFT JOIN taxes t ON ii.item_tax_id = t.tax_id
            WHERE i.invoice_id = :invoice_id
            GROUP BY i.invoice_id");
            $stmt->execute(['invoice_id' => $invoice_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = round($result['total_invoiced'] + $result['invoice_discount_amount'], 2);
        } else if ($type == 'subtotal') {
            $stmt = $this->pdo->prepare("SELECT SQL_CACHE 
                i.invoice_discount_amount,
                SUM(ii.item_quantity * ii.item_price - ii.item_discount) AS subtotal 
            FROM invoices i
            LEFT JOIN invoice_items ii ON i.invoice_id = ii.item_invoice_id
            WHERE i.invoice_id = :invoice_id
            GROUP BY i.invoice_id");
            $stmt->execute(['invoice_id' => $invoice_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = round($result['subtotal'] + $result['invoice_discount_amount'], 2);
        } else if ($type == 'tax') {
            $stmt = $this->pdo->prepare("SELECT SQL_CACHE 
                i.invoice_discount_amount,
                SUM((ii.item_quantity * ii.item_price - ii.item_discount) * (IFNULL(t.tax_percent, 0)/100)) AS total_tax
            FROM invoices i
            LEFT JOIN invoice_items ii ON i.invoice_id = ii.item_invoice_id
            LEFT JOIN taxes t ON ii.item_tax_id = t.tax_id
            WHERE i.invoice_id = :invoice_id
            GROUP BY i.invoice_id");
            $stmt->execute(['invoice_id' => $invoice_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = round($result['total_tax'], 2);
        } else {
            $total = 0;
        }

        $this->setCachedInvoiceTotals($invoice_id, [$type => $total], true);
        return $total;
    }

    /**
     * Gets payments for an invoice
     * 
     * @param int $invoice_id Invoice ID
     * @param bool $sum Whether to return sum instead of details
     * @param bool $useCache Whether to use cached values
     * @return array|float Array of payments or sum
     */
    public function getPaymentsByInvoice($invoice_id, $sum = false, $useCache = true) {
        $cachedTotals = $this->getCachedInvoiceTotals($invoice_id, $useCache);
        if ($cachedTotals !== null && isset($cachedTotals['paid'])) {
            return $sum ? $cachedTotals['paid'] : $cachedTotals['payments'];
        }

        if ($sum) {
            $stmt = $this->pdo->prepare("SELECT SQL_CACHE SUM(payment_amount) AS total_paid FROM payments WHERE payment_invoice_id = :invoice_id");
            $stmt->execute(['invoice_id' => $invoice_id]);
            $total_paid = $stmt->fetch(PDO::FETCH_ASSOC)['total_paid'] ?? 0;
            $this->setCachedInvoiceTotals($invoice_id, ['paid' => $total_paid], true);
            return $total_paid;
        } else {
            $stmt = $this->pdo->prepare("SELECT SQL_CACHE * FROM payments WHERE payment_invoice_id = :invoice_id");
            $stmt->execute(['invoice_id' => $invoice_id]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->setCachedInvoiceTotals($invoice_id, ['payments' => $payments, 'paid' => array_sum(array_column($payments, 'payment_amount'))], true);
            return $payments;
        }
    }

    /**
     * Calculates current balance for an invoice
     * 
     * @param int $invoice_id Invoice ID
     * @param bool $useCache Whether to use cached values
     * @return float Current balance
     */
    public function getInvoiceBalance($invoice_id, $useCache = true) {
        $cachedTotals = $this->getCachedInvoiceTotals($invoice_id, $useCache);
        if ($cachedTotals !== null && isset($cachedTotals['balance'])) {
            return $cachedTotals['balance'];
        }

        $total = $this->getInvoiceAmount($invoice_id, 'total', $useCache);
        $paid = $this->getPaymentsByInvoice($invoice_id, true, $useCache);
        $balance = $total - $paid;

        $this->setCachedInvoiceTotals($invoice_id, ['balance' => $balance], true);
        return $balance;
    }

    /**
     * Generates monthly sales tax report
     * 
     * @param int|false $year Year or false for current year
     * @param int|false $month Month or false for all months
     * @return array Monthly sales tax data
     */
    public function getMonthlySalesTaxReport($year = false, $month = false) {
        if (!$year) {
            $year = date('Y');
        }

        // Step 1: Get all payments received in the specified period
        $sqlPayments = "
            SELECT 
                payments.payment_id,
                payments.payment_amount,
                payments.payment_date,
                payments.payment_invoice_id
            FROM payments
            WHERE YEAR(payments.payment_date) = :year
            " . ($month ? "AND MONTH(payments.payment_date) = :month" : "") . "
        ";
        $stmtPayments = $this->pdo->prepare($sqlPayments);
        $params = ['year' => $year];
        if ($month) {
            $params['month'] = $month;
        }
        $stmtPayments->execute($params);
        $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);

        if (empty($payments)) {
            error_log("No payments found for year $year" . ($month ? ", month $month" : ""));
            return []; // No payments found for specified period
        }

        // Step 2: Get invoice items for the payments
        $invoiceIds = array_column($payments, 'payment_invoice_id');
        $placeholders = implode(',', array_fill(0, count($invoiceIds), '?'));

        $sqlItems = "
            SELECT 
                invoice_items.item_invoice_id,
                invoice_items.item_price,
                invoice_items.item_quantity,
                invoice_items.item_discount,
                invoice_items.item_tax_id
            FROM invoice_items
            WHERE invoice_items.item_invoice_id IN ($placeholders)
        ";
        $stmtItems = $this->pdo->prepare($sqlItems);
        $stmtItems->execute($invoiceIds);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Organize data
        $invoiceData = [];
        foreach ($payments as $payment) {
            $invoiceId = $payment['payment_invoice_id'];
            if (!isset($invoiceData[$invoiceId])) {
                $invoiceData[$invoiceId] = [
                    'payment_amount' => 0,
                    'items' => [],
                ];
            }
            $invoiceData[$invoiceId]['payment_amount'] += $payment['payment_amount'];
        }

        foreach ($items as $item) {
            $invoiceId = $item['item_invoice_id'];
            if (!isset($invoiceData[$invoiceId])) {
                continue; // Payment data not found
            }
            $itemPrice = isset($item['item_price']) ? $item['item_price'] : 0;
            $itemQuantity = isset($item['item_quantity']) ? $item['item_quantity'] : 0;
            $itemDiscount = isset($item['item_discount']) ? $item['item_discount'] : 0;

            $itemTotal = ($itemPrice * $itemQuantity) - $itemDiscount;

            $invoiceData[$invoiceId]['items'][] = [
                'item_total' => $itemTotal,
                'item_tax_id' => isset($item['item_tax_id']) ? $item['item_tax_id'] : 0,
            ];
        }

        // Initialize monthly sales array
        $monthly_sales = [];
        // Possible sales types
        $sales_types = ['Taxable Sales', 'Tax Exempt Sales', 'Total Sales'];

        // Initialize monthly sales for each month and sales type
        for ($m = 1; $m <= 12; $m++) {
            foreach ($sales_types as $type) {
                $monthly_sales[$m][$type] = 0;
            }
        }

        // Accumulate sales amounts
        foreach ($payments as $payment) {
            $paymentDate = $payment['payment_date'];
            $month = (int) date('n', strtotime($paymentDate));
            $invoiceId = $payment['payment_invoice_id'];
            $paymentAmount = $payment['payment_amount'];

            if (!isset($invoiceData[$invoiceId])) {
                continue; // Invoice data not found
            }

            // Calculate the proportion of the payment amount for each item
            $totalInvoiceAmount = array_sum(array_column($invoiceData[$invoiceId]['items'], 'item_total'));
            if ($totalInvoiceAmount == 0) {
                continue; // Avoid division by zero
            }

            foreach ($invoiceData[$invoiceId]['items'] as $item) {
                $itemTotal = $item['item_total']; // Net of sales tax
                $taxID = $item['item_tax_id'];
                $taxRate = $item['tax_percent'] / 100; // Method to retrieve tax rate

                // Calculate proportional payment for the item
                $proportion = $itemTotal / $totalInvoiceAmount;
                $proportionalPayment = $paymentAmount * $proportion;

                // Calculate the amount of sales tax for this proportional payment
                $proportionalSalesTax = $proportionalPayment * ($taxRate / (100 + $taxRate));

                // Net amount of the proportional payment without sales tax
                $proportionalPaymentNetOfTax = $proportionalPayment - $proportionalSalesTax;

                // Determine sales type and accumulate net sales
                if ($taxID > 0) {
                    // Taxable Sales
                    $monthly_sales[$month]['Taxable Sales'] += $proportionalPaymentNetOfTax;
                } else {
                    // Tax Exempt Sales
                    $monthly_sales[$month]['Tax Exempt Sales'] += $proportionalPaymentNetOfTax;
                }

                // Add to Total Sales (net of sales tax)
                $monthly_sales[$month]['Total Sales'] += $proportionalPaymentNetOfTax;
            }
        }

        return $monthly_sales;
    }

    /**
     * Calculates total sales for a specific month
     * 
     * @param int $month Month number
     * @param int $year Year
     * @return float Total sales amount
     */
    public function getTotalSales($month, $year) {
        //Calculate total sales (payments) for a given month from the database
        $stmt = $this->pdo->prepare("SELECT SUM(payment_amount) FROM payments WHERE MONTH(payment_date) = :month AND YEAR(payment_date) = :year");
        $stmt->execute(['month' => $month, 'year' => $year]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['SUM(payment_amount)'];
    }

    /**
     * Generates collections report for all clients
     * 
     * @return array Collections report data
     */
    public function getCollectionsReport() {
        $stmt = $this->pdo->query("SELECT  * FROM clients
        WHERE client_archived_at IS NULL
        ORDER BY client_name desc");
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = 0;

        foreach ($clients as $client) {
            $client['balance'] = $this->getClientBalance($client['client_id']); #This is miscalcuting the balance
            $total += $client['balance'];
            $client['monthly_recurring_amount'] = $this->getMonthlySubscriptionAmount($client['client_id']);
            $client['past_due_amount'] = $this->getPastDueAmount($client['client_id']);
            //Get billing contact phone
            $client['contact_phone'] = $this->client->getClientContact($client['client_id'], 'billing')['contact_phone'] ?? $this->client->getClientContact($client['client_id'])['contact_phone'] ?? $this->client->getClientContact($client['client_id'])['contact_mobile'];

            //Save changes to array
            $data_clients[] = $client;
        }

        $data['collections_report']['clients'] = $data_clients;
        $data['collections_report']['total_balance'] = $total;
        $data['past_due_filter'] = 2;
        return $data;
    }

    /**
     * Calculates monthly subscription amount
     * 
     * @param int|null $client_id Client ID or null for all clients
     * @return float Monthly subscription amount
     */
    public function getMonthlySubscriptionAmount($client_id = null) {
        if ($client_id != null) {
            $stmt = $this->pdo->prepare("
                SELECT SQL_CACHE
                    products.product_price,
                    subscriptions.subscription_product_quantity,
                    subscriptions.subscription_term
                FROM subscriptions
                LEFT JOIN products ON subscriptions.subscription_product_id = products.product_id
                WHERE subscription_client_id = :client_id
            ");
            $stmt->execute(['client_id' => $client_id]);
        } else {
            $stmt = $this->pdo->query("
                SELECT SQL_CACHE
                    products.product_price,
                    subscriptions.subscription_product_quantity,
                    subscriptions.subscription_term
                FROM subscriptions
                LEFT JOIN products ON subscriptions.subscription_product_id = products.product_id
            ");
        }
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $monthly_amount = 0;

        foreach ($subscriptions as $subscription) {
            $amount = $subscription['product_price'] * $subscription['subscription_product_quantity'];
            if ($subscription['subscription_term'] === 'yearly') {
                $amount = $amount / 12; // Convert yearly amount to monthly
            }
            $monthly_amount += $amount;
        }

        return $monthly_amount;
    }

    /**
     * Gets past due amount for a client
     * 
     * @param int $client_id Client ID
     * @return float Past due amount
     */
    public function getPastDueAmount($client_id) {
        return $this->getClientagingBalance($client_id, 30, 9999999);
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

    /**
     * Gets statement data for a client
     * 
     * @param int $client_id Client ID
     * @return array Statement data
     */
    public function getStatement($client_id) {
        $client_id = intval($client_id);

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

        // Fetch all transactions, payments, and invoice items in a single query
        $sql_client_transactions = "
        SELECT 
            i.invoice_id, 
            i.invoice_date, 
            i.invoice_status,
            SUM(ii.item_quantity * ii.item_price - ii.item_discount) AS invoice_subtotal,
            SUM((ii.item_quantity * ii.item_price - ii.item_discount) * (IFNULL(t.tax_percent, 0) / 100)) AS invoice_tax,
            p.payment_id,
            p.payment_date,
            p.payment_amount,
            p.payment_method
        FROM 
            invoices i
        LEFT JOIN 
            invoice_items ii ON i.invoice_id = ii.item_invoice_id
        LEFT JOIN
            taxes t ON ii.item_tax_id = t.tax_id
        LEFT JOIN 
            payments p ON i.invoice_id = p.payment_invoice_id
        WHERE 
            i.invoice_client_id = :client_id
            AND i.invoice_status NOT IN ('Draft', 'Cancelled')
        GROUP BY
            i.invoice_id, p.payment_id
        ORDER BY 
            i.invoice_date DESC, p.payment_date DESC
        ";

        $stmt = $this->pdo->prepare($sql_client_transactions);
        $stmt->execute(['client_id' => $client_id]);
        $result_client_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $transactions = [];
        foreach ($result_client_transactions as $row) {
            $invoice_id = $row['invoice_id'];
            if (!isset($transactions[$invoice_id])) {
                $invoice_subtotal = floatval($row['invoice_subtotal']);
                $invoice_tax = floatval($row['invoice_tax']);
                $invoice_amount = $invoice_subtotal + $invoice_tax;
                
                $transactions[$invoice_id] = [
                    'invoice_id' => $invoice_id,
                    'invoice_prefix' => $row['invoice_prefix'],
                    'invoice_number' => $row['invoice_number'],
                    'invoice_date' => $row['invoice_date'],
                    'invoice_status' => $row['invoice_status'],
                    'invoice_amount' => $invoice_amount,
                    'invoice_balance' => $this->getInvoiceBalance($invoice_id),
                    'payments' => []
                ];
            }
            if ($row['payment_id']) {
                $transactions[$invoice_id]['payments'][] = [
                    'payment_id' => $row['payment_id'],
                    'payment_date' => $row['payment_date'],
                    'payment_amount' => $row['payment_amount'],
                    'payment_method' => $row['payment_method']
                ];
            }
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
            'transactions' => $transactions,
            'unpaid_invoices' => $client_invoices,
            'aging_balance' => $this->getClientagingBalance($client_id, 0, 30),
            'aging_balance_30' => $this->getClientagingBalance($client_id, 31, 60),
            'aging_balance_60' => $this->getClientagingBalance($client_id, 61, 90),
            'aging_balance_90' => $this->getClientagingBalance($client_id, 91, null),
        ];


    }

    /**
     * Calculates aging balance for a client
     * 
     * @param int $client_id Client ID
     * @param int $from Starting days
     * @param int|null $to Ending days or null for no limit
     * @return float Aging balance
     */
    public function getClientagingBalance($client_id, $from, $to) {
        $client_id = intval($client_id);
        $from = intval($from);
        $to = intval($to);

        if ($to == null) {
            //If to is null, set it to the first day in the database
            $to = date('Y-m-d', strtotime('2000-01-01'));
        }

        // Get from and to dates for the aging balance by subtracting the number of days from the current date
        $from_date = date('Y-m-d', strtotime('-' . $from . ' days'));
        $to_date = date('Y-m-d', strtotime('-' . $to . ' days'));
    
        //Get all invoice ids that are not draft or cancelled from the date range
        $sql = "SELECT SQL_CACHE invoice_id FROM invoices
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
        }
        return $balance;
    }

    /**
     * Gets receivables for a specific month
     * 
     * @param int $month Month number
     * @param int $year Year
     * @return float Total receivables
     */
    public function getRecievables($month, $year) {
        $start_day = date('Y-m-01', strtotime($year . '-' . $month . '-01'));
        $end_day = date('Y-m-t', strtotime($year . '-' . $month . '-01'));

        $stmt = $this->pdo->prepare("SELECT invoice_id FROM invoices WHERE invoice_due >= :start_day AND invoice_due <= :end_day");
        $stmt->execute(['start_day' => $start_day, 'end_day' => $end_day]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_invoices = 0;

        foreach ($invoices as $invoice) {
            $total_invoices += $this->getInvoiceBalance($invoice['invoice_id']);
        }

        if (empty($invoices)) {
            // Add subscriptions to the total
            $subscriptions = $this->getMonthlySubscriptionAmount(); 
            $total_invoices += $subscriptions;
        }

        if ($total_invoices < 0) {
            $total_invoices = 0;
        }

        return $total_invoices;
    }

    /**
     * Calculates total income for a month
     * 
     * @param int $month Month number
     * @param int $year Year
     * @return float Total income
     */
    public function getIncomeTotal($month, $year) {
        $start_day = date('Y-m-01', strtotime($year . '-' . $month . '-01'));
        $end_day = date('Y-m-t', strtotime($year . '-' . $month . '-01'));

        $stmt = $this->pdo->prepare("SELECT payment_amount FROM payments WHERE payment_date >= :start_day AND payment_date <= :end_day");
        $stmt->execute(['start_day' => $start_day, 'end_day' => $end_day]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_payments = 0;

        foreach ($payments as $payment) {
            $total_payments += $payment['payment_amount'];
        }

        return $total_payments;
    }

    /**
     * Gets total expenses for a period
     * 
     * @param int|null $month Month number or null for all
     * @param int|null $year Year or null for all
     * @return float|array Total expenses or expense records
     */
    public function getExpensesTotal($month = null, $year = null) {
        if ($month == null && $year == null) {
            //return all expenses
            $stmt = $this->pdo->prepare("SELECT * FROM expenses LEFT JOIN categories ON expenses.expense_category_id = categories.category_id");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $start_day = date('Y-m-01', strtotime($year . '-' . $month . '-01'));
        $end_day = date('Y-m-t', strtotime($year . '-' . $month . '-01'));

        $stmt = $this->pdo->prepare("SELECT expense_amount FROM expenses WHERE expense_date >= :start_day AND expense_date <= :end_day");
        $stmt->execute(['start_day' => $start_day, 'end_day' => $end_day]);
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_expenses = 0;

        foreach ($expenses as $expense) {
            $total_expenses += $expense['expense_amount'];
        }

        return $total_expenses;
    }

    /**
     * Calculates profit for a specific month
     * 
     * @param int $month Month number
     * @param int $year Year
     * @return float Profit amount
     */
    public function getProfit($month, $year) {
        return $this->getIncomeTotal($month, $year) - $this->getExpensesTotal($month, $year);
    }

    /**
     * Counts unbilled tickets for a period
     * 
     * @param int $month Month number
     * @param int $year Year
     * @return int Number of unbilled tickets
     */
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

    /**
     * Calculates total quotes for a period
     * 
     * @param int $month Month number
     * @param int $year Year
     * @return float Total quote amount
     */
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

    /**
     * Calculates total accepted quotes for a period
     * 
     * @param int $month Month number
     * @param int $year Year
     * @return float Total accepted quote amount
     */
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

    /**
     * Performs polynomial regression calculation
     * 
     * @param array $x X values
     * @param array $y Y values
     * @param int $degree Polynomial degree
     * @return array Coefficients
     */
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

    /**
     * Solves linear system of equations
     * 
     * @param array $matrix Coefficient matrix
     * @param array $vector Constants vector
     * @return array Solution vector
     */
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

    /**
     * Gets income breakdown by category
     * 
     * @param int $month Month number
     * @param int $year Year
     * @return array Income by category
     */
    public function getIncomeByCategory($month, $year) {
        $start_day = date('Y-m-01', strtotime($year . '-' . $month . '-01'));
        $end_day   = date('Y-m-t', strtotime($year . '-' . $month . '-01'));

        // Get all payments for the month
        $stmt = $this->pdo->prepare("
            SELECT * FROM payments 
            WHERE payment_created_at >= :start_day AND payment_created_at <= :end_day
        ");
        $stmt->execute(['start_day' => $start_day, 'end_day' => $end_day]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $category_profit = [];

        // For each payment, get the category from the invoice items
        foreach ($payments as $payment) {
            $stmt = $this->pdo->prepare("
                SELECT 
                    invoice_items.*,
                    item_categories.category_name AS item_category_name,
                    invoices.invoice_id,
                    invoices.invoice_category_id,
                    invoice_categories.category_name AS invoice_category_name
                FROM invoice_items
                LEFT JOIN categories AS item_categories ON invoice_items.item_category_id = item_categories.category_id
                LEFT JOIN invoices ON invoice_items.item_invoice_id = invoices.invoice_id
                LEFT JOIN categories AS invoice_categories ON invoices.invoice_category_id = invoice_categories.category_id
                WHERE invoice_items.item_invoice_id = :invoice_id
            ");
            $stmt->execute(['invoice_id' => $payment['payment_invoice_id']]);
            $invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate total invoice amount
            $total_invoice_amount = array_sum(array_map(function($item) {
                return $item['item_price'] * $item['item_quantity'] - $item['item_discount'];
            }, $invoice_items));

            // If total invoice amount is zero, distribute payment equally among items
            $item_count = count($invoice_items);
            $equal_distribution = $item_count > 0 ? $payment['payment_amount'] / $item_count : 0;

            foreach ($invoice_items as $invoice_item) {
                // Use item category if available, otherwise use invoice category, else 'Uncategorized'
                $category_name = $invoice_item['item_category_name'] 
                                 ?? $invoice_item['invoice_category_name'] 
                                 ?? 'Uncategorized';

                if (!isset($category_profit[$category_name])) {
                    $category_profit[$category_name] = 0;
                }

                if ($total_invoice_amount > 0) {
                    // Calculate the proportion of this item to the total invoice
                    $item_amount = $invoice_item['item_price'] * $invoice_item['item_quantity'] - $invoice_item['item_discount'];
                    $item_proportion = $item_amount / $total_invoice_amount;
                    // Add the proportional payment amount to the category
                    $category_profit[$category_name] += $payment['payment_amount'] * $item_proportion;
                } else {
                    // If total invoice amount is zero, use equal distribution
                    $category_profit[$category_name] += $equal_distribution;
                }
            }
        }

        // Round the final amounts
        foreach ($category_profit as &$amount) {
            $amount = round($amount, 2);
        }

        return $category_profit;
    }

    /**
     * Gets expenses breakdown by category
     * 
     * @param int $month Month number
     * @param int $year Year
     * @return array Expenses by category
     */
    public function getExpensesByCategory($month, $year) {
        $start_day = date('Y-m-01', strtotime($year . '-' . $month . '-01'));
        $end_day = date('Y-m-t', strtotime($year . '-' . $month . '-01'));

        $stmt = $this->pdo->prepare("SELECT * FROM expenses
        LEFT JOIN categories ON expenses.expense_category_id = categories.category_id
        WHERE expense_date >= :start_day AND expense_date <= :end_day");
        $stmt->execute(['start_day' => $start_day, 'end_day' => $end_day]);
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $category_expense = [];

        foreach ($expenses as $expense) {
            $category_name = $expense['category_name'] ?? 'Uncategorized';

            if (!isset($category_expense[$category_name])) {
                $category_expense[$category_name] = 0;
            }
            $category_expense[$category_name] += $expense['expense_amount'];
        }

        foreach ($category_expense as $category_name => $expense_amount) {
            $category_expense[$category_name] = round($expense_amount, 2);
        }

        return $category_expense;
    }

    /**
     * Generates profit/loss report
     * 
     * @param int|null $year Year or null for current year
     * @return array Profit/loss report data
     */
    public function getProfitLossReport($year = null) {
        if (!$year) {
            $year = date('Y');
        }

        $sql = "
        SELECT SQL_CACHE
            COALESCE(
                item_categories.category_name,
                invoice_categories.category_name,
                'Uncategorized'
            ) AS category_name,
            SUM(
                CASE 
                    WHEN payments.payment_amount IS NOT NULL THEN 
                        (invoice_items.item_price * invoice_items.item_quantity - invoice_items.item_discount) * 
                        (payments.payment_amount / (
                            SELECT SUM(ii.item_price * ii.item_quantity - ii.item_discount)
                            FROM invoice_items ii
                            WHERE ii.item_invoice_id = invoices.invoice_id
                        ))
                    ELSE 0 
                END
            ) AS total_income
        FROM 
            invoice_items
        LEFT JOIN 
            invoices ON invoice_items.item_invoice_id = invoices.invoice_id
        LEFT JOIN 
            payments ON invoices.invoice_id = payments.payment_invoice_id
        LEFT JOIN 
            categories AS item_categories ON invoice_items.item_category_id = item_categories.category_id
        LEFT JOIN 
            categories AS invoice_categories ON invoices.invoice_category_id = invoice_categories.category_id
        WHERE 
            YEAR(payments.payment_date) = :year
            AND payments.payment_amount > 0
        GROUP BY 
            COALESCE(
                item_categories.category_name,
                invoice_categories.category_name,
                'Uncategorized'
            )
        ORDER BY 
            total_income DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['year' => $year]);
        $income_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $profit_loss_report = [];

        foreach ($income_by_category as $row) {
            $category_name = $row['category_name'];
            if (!isset($profit_loss_report[$category_name])) {
                $profit_loss_report[$category_name] = [
                    'category_name' => $category_name,
                    'total_income' => 0,
                    'total_expense' => 0
                ];
            }
            $profit_loss_report[$category_name]['total_income'] += $row['total_income'];

            $stmt = $this->pdo->prepare("SELECT SUM(expense_amount) AS total_expense FROM expenses 
                                         LEFT JOIN categories ON expenses.expense_category_id = categories.category_id
                                         WHERE categories.category_name = :category_name AND YEAR(expense_date) = :year");
            $stmt->execute(['category_name' => $category_name, 'year' => $year]);
            $expense = $stmt->fetch(PDO::FETCH_ASSOC);
            $profit_loss_report[$category_name]['total_expense'] += $expense['total_expense'] ?? 0;
        }

        // Add categories that only appear in expenses
        $stmt = $this->pdo->query("SELECT category_name FROM categories WHERE category_id NOT IN (SELECT invoice_category_id FROM invoices)");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($categories as $category) {
            $category_name = $category['category_name'];
            if (!isset($profit_loss_report[$category_name])) {
                $total_expense_stmt = $this->pdo->prepare("SELECT SUM(expense_amount) AS total_expense FROM expenses 
                                                           LEFT JOIN categories ON expenses.expense_category_id = categories.category_id
                                                           WHERE categories.category_name = :category_name AND YEAR(expense_date) = :year");
                $total_expense_stmt->execute(['category_name' => $category_name, 'year' => $year]);
                $total_expense = $total_expense_stmt->fetch(PDO::FETCH_ASSOC);
                $profit_loss_report[$category_name] = [
                    'category_name' => $category_name,
                    'total_income' => 0,
                    'total_expense' => $total_expense['total_expense'] ?? 0
                ];
            }
        }

        // Convert associative array to indexed array
        $profit_loss_report = array_values($profit_loss_report);

        // Sort by category name
        usort($profit_loss_report, function($a, $b) {
            return strcmp($a['category_name'], $b['category_name']);
        });

        return $profit_loss_report;
    }

    /**
     * Gets income total by client
     * 
     * @param int $month Month number
     * @param int $year Year
     * @return array Income totals by client
     */
    public function getIncomeTotalByClientReport($month, $year) {
        $start_day = date('Y-m-01', strtotime($year . '-' . $month . '-01'));
        $end_day = date('Y-m-t', strtotime($year . '-' . $month . '-01'));

        $stmt = $this->pdo->prepare("
            SELECT 
                clients.client_name,
                SUM(payments.payment_amount) AS total_income
            FROM payments
            JOIN clients ON payments.payment_client_id = clients.client_id
            WHERE payments.payment_created_at >= :start_day AND payments.payment_created_at <= :end_day
            GROUP BY clients.client_id
        ");
        $stmt->execute(['start_day' => $start_day, 'end_day' => $end_day]);
        $incomes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $incomes;
    }

    /**
     * Retrieves all accounts
     * 
     * @return array Array of accounts
     */
    public function getAccounts() {
        $stmt = $this->pdo->query("SELECT * FROM accounts");
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $accounts;
    }

    /**
     * Gets account details
     * 
     * @param int $account_id Account ID
     * @return array Account details
     */
    public function getAccount($account_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM accounts WHERE account_id = :account_id");
        $stmt->execute(['account_id' => $account_id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        return $account;
    }

    /**
     * Checks Plaid link status for an account
     * 
     * @param int $account_id Account ID
     * @return string Link status
     */
    public function checkPlaidLinkStatus($account_id) {
        //check if the account has an access token
        $stmt = $this->pdo->prepare("SELECT * FROM plaid_accounts
        LEFT JOIN accounts ON plaid_accounts.plaid_account_id = accounts.plaid_id
        WHERE accounts.account_id = :account_id");
        $stmt->execute(['account_id' => $account_id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($account['plaid_access_token'] == null) {
            return 'Unlinked';
        }
        return 'Linked';
    }

    /**
     * Gets transactions for an account
     * 
     * @param int $account_id Account ID
     * @return array Array of transactions
     */
    public function getAccountTransactions($account_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM bank_transactions
        LEFT JOIN plaid_accounts ON bank_transactions.bank_account_id = plaid_accounts.plaid_account_id
        LEFT JOIN accounts ON plaid_accounts.plaid_account_id = accounts.plaid_id
        WHERE accounts.account_id = :account_id
        ORDER BY bank_transactions.date DESC");
        $stmt->execute(['account_id' => $account_id]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $transactions;
    }

    /**
     * Retrieves expense details
     * 
     * @param int $expense_id Expense ID
     * @return array Expense details
     */
    public function getExpense($expense_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM expenses WHERE expense_id = :expense_id");
        $stmt->execute(['expense_id' => $expense_id]);
        $expense = $stmt->fetch(PDO::FETCH_ASSOC);
        return $expense;
    }

    /**
     * Gets bank transactions
     * 
     * @param bool $unreconciled Whether to get only unreconciled transactions
     * @return array Array of transactions
     */
    public function getBankTransactions($unreconciled = true) {
        if ($unreconciled) {
            $stmt = $this->pdo->query("SELECT * FROM bank_transactions WHERE reconciled = 0");
        } else {
            $stmt = $this->pdo->query("SELECT * FROM bank_transactions");
        }
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $transactions;
    }

    /**
     * Gets bank transaction details
     * 
     * @param int $transaction_id Transaction ID
     * @return array Transaction details
     */
    public function getBankTransaction($transaction_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM bank_transactions WHERE transaction_id = :transaction_id");
        $stmt->execute(['transaction_id' => $transaction_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        return $transaction;
    }

    /**
     * Retrieves Plaid-linked accounts
     * 
     * @return array Array of Plaid accounts
     */
    public function getPlaidAccounts() {
        $stmt = $this->pdo->query("SELECT * FROM  accounts LEFT JOIN plaid_accounts ON plaid_accounts.plaid_account_id = accounts.plaid_id
        WHERE plaid_access_token IS NOT NULL
        ");
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $accounts;
    }

    /**
     * Matches transaction to potential expenses
     * 
     * @param int $transaction_id Transaction ID
     * @return array Matching expenses
     */
    public function matchExpense($transaction_id) {
        //get the transaction details from the bank_transactions table
        $stmt = $this->pdo->prepare("SELECT * FROM bank_transactions WHERE transaction_id = :transaction_id");
        $stmt->execute(['transaction_id' => $transaction_id]);
        $transaction = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $upper_amount = $transaction['amount'] * -1.05;
        $lower_amount = $transaction['amount'] * -0.95;
        $date_upper = date('Y-m-d', strtotime($transaction['date'] . ' + 10 day'));
        $date_lower = date('Y-m-d', strtotime($transaction['date'] . ' - 10 day'));

        $stmt = $this->pdo->prepare("SELECT * FROM expenses
        LEFT JOIN clients ON expenses.expense_client_id = clients.client_id
        WHERE expense_amount >= :lower_amount AND expense_amount <= :upper_amount AND expense_date >= :date_lower AND expense_date <= :date_upper");
        $stmt->execute(['lower_amount' => $lower_amount, 'upper_amount' => $upper_amount, 'date_lower' => $date_lower, 'date_upper' => $date_upper]);
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $expenses;
    }

    /**
     * Matches transaction to potential income
     * 
     * @param int $transaction_id Transaction ID
     * @return array Matching income records
     */
    public function matchIncome($transaction_id) {
        //get the transaction details from the bank_transactions table
        $stmt = $this->pdo->prepare("SELECT * FROM bank_transactions WHERE transaction_id = :transaction_id");
        $stmt->execute(['transaction_id' => $transaction_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        $upper_amount = $transaction['amount'] * -1.05;
        $lower_amount = $transaction['amount'] * -0.95;
        $date_upper = date('Y-m-d', strtotime($transaction['date'] . ' + 10 day'));
        $date_lower = date('Y-m-d', strtotime($transaction['date'] . ' - 10 day'));

        $stmt = $this->pdo->prepare("SELECT * FROM payments
        LEFT JOIN invoices ON payments.payment_invoice_id = invoices.invoice_id
        LEFT JOIN clients ON invoices.invoice_client_id = clients.client_id
        WHERE payment_amount >= :lower_amount AND payment_amount <= :upper_amount AND payment_date >= :date_lower AND payment_date <= :date_upper");
        $stmt->execute(['lower_amount' => $lower_amount, 'upper_amount' => $upper_amount, 'date_lower' => $date_lower, 'date_upper' => $date_upper]);
        $incomes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $incomes;
    }

    /**
     * Gets all expense categories
     * 
     * @return array Array of expense categories
     */
    public function getExpenseCategories() {
        $stmt = $this->pdo->query("SELECT * FROM categories");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $categories;
    }

    /**
     * Generates unbilled tickets report
     * 
     * @return array Report data
     */
    public function getUnbilledTicketsReport() {
        $stmt = $this->pdo->query("SELECT * FROM tickets
        LEFT JOIN ticket_statuses ON tickets.ticket_status = ticket_statuses.ticket_status_id
        LEFT JOIN clients ON tickets.ticket_client_id = clients.client_id
        LEFT JOIN users ON tickets.ticket_assigned_to = users.user_id
        WHERE ticket_invoice_id = 0
        AND ticket_status >= 4
        AND ticket_billable = 1
        ORDER BY ticket_status DESC, ticket_created_at ASC
        ");
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $tickets;
    }

    /**
     * Gets available subscriptions for a client
     * 
     * @param int|null $client_id Client ID or null for all
     * @return array Available subscriptions
     */
    public function getAvailableSubscriptions($client_id = null) {
        if ($client_id) {
            $stmt = $this->pdo->prepare("SELECT product_name FROM products
            WHERE product_subscription = 1
            AND product_id NOT IN (SELECT subscription_product_id FROM subscriptions WHERE subscription_client_id = :client_id)");
            $stmt->execute(['client_id' => $client_id]);
        } else {
            $stmt = $this->pdo->query("SELECT product_name FROM products
            WHERE product_subscription = 1");
        }
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $subscriptions;
    }

    /**
     * Gets current subscriptions for a client
     * 
     * @param int $client_id Client ID
     * @return array Current subscriptions
     */
    public function getSubscribedSubscriptions($client_id) {
        $stmt = $this->pdo->prepare("SELECT DISTINCT product_name FROM subscriptions
        LEFT JOIN products ON subscriptions.subscription_product_id = products.product_id
        WHERE subscription_client_id = :client_id");
        $stmt->execute(['client_id' => $client_id]);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $subscriptions;
    }

    /**
     * Gets opportunities
     * 
     * @return array Array of opportunities
     */
    public function getOpportunities() {
        return [];
    }

    /**
     * Counts occurrences of a payment reference
     * 
     * @param string $payment_reference Payment reference
     * @return int Number of occurrences
     */
    public function getPaymentReferenceCount($payment_reference) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM payments WHERE payment_reference = :payment_reference");
        $stmt->execute(['payment_reference' => $payment_reference]);
        $count = $stmt->fetchColumn();
        return $count;
    }
}

