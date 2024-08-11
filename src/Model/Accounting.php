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
}