<?php
// src/Model/Accounting.php

namespace Twetech\Nestogy\Model;

use PDO;

class Accounting {
    private $pdo;
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
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
            $stmt = $this->pdo->query("SELECT * FROM payments ORDER BY payment_date DESC");
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
            $stmt = $this->pdo->prepare("SELECT * FROM subscriptions WHERE subscription_client_id = :client_id");
            $stmt->execute(['client_id' => $client_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM subscriptions");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    public function getSubscription($subscription_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM subscriptions WHERE subscription_id = :subscription_id");
        $stmt->execute(['subscription_id' => $subscription_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getProduct($product_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE product_id = :product_id");
        $stmt->execute(['product_id' => $product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getPayment($payment_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE payment_id = :payment_id");
        $stmt->execute(['payment_id' => $payment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getPaymentsByReference($reference) {
        $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE payment_reference = :reference");
        $stmt->execute(['reference' => $reference]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}