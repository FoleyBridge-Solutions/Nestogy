<?php

// src/Model/Invoice.php

namespace Twetech\Nestogy\Model;

use PDO;

/**
 * Class Invoice
 * Handles database operations for invoices
 * 
 * @package Twetech\Nestogy\Model
 */
class Invoice {
    /** @var PDO Database connection */
    private $pdo;

    /**
     * Invoice constructor
     * 
     * @param PDO $pdo Database connection instance
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves all invoices from the database
     * 
     * @return array Array of all invoices
     */
    public function getInvoices() {
        $stmt = $this->pdo->query("SELECT * FROM invoices");
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a specific invoice by ID
     * 
     * @param int $invoice_id The ID of the invoice to retrieve
     * @return array|false Invoice data array or false if not found
     */
    public function getInvoice($invoice_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM invoices WHERE invoice_id = :invoice_id");
        $stmt->execute(['invoice_id' => $invoice_id]);
        return $stmt->fetch();
    }    
}