<?php
// src/Model/Administration.php

namespace Twetech\Nestogy\Model;

use PDO;

/**
 * Administration class handles database operations for various administrative entities
 */
class Administration {
    /** @var PDO Database connection instance */
    private $pdo;

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection instance
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves all API keys from the database
     *
     * @return array Array of API keys
     */
    public function getAPIKeys() {
        $stmt = $this->pdo->query("SELECT * FROM api_keys");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all account types from the database
     *
     * @return array Array of account types
     */
    public function getAccountTypes() {
        $stmt = $this->pdo->query("SELECT * FROM account_types");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all vendor templates from the database
     *
     * @return array Array of vendor templates
     */
    public function getVendorTemplates() {
        $stmt = $this->pdo->query("SELECT * FROM vendor_templates");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all license templates from the database
     *
     * @return array Array of license templates
     */
    public function getLicenseTemplates() {
        $stmt = $this->pdo->query("SELECT * FROM license_templates");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all document templates from the database
     *
     * @return array Array of document templates
     */
    public function getDocumentTemplates() {
        $stmt = $this->pdo->query("SELECT * FROM document_templates");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all audit logs from the database
     *
     * @return array Array of audit logs
     */
    public function getAuditLogs() {
        $stmt = $this->pdo->query("SELECT * FROM audit_logs");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all tags from the database
     *
     * @return array Array of tags
     */
    public function getTags() {
        $stmt = $this->pdo->query("SELECT * FROM tags");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all categories from the database
     *
     * @return array Array of categories
     */
    public function getCategories() {
        $stmt = $this->pdo->query("SELECT * FROM categories");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all taxes from the database
     *
     * @return array Array of taxes
     */
    public function getTaxes() {
        $stmt = $this->pdo->query("SELECT * FROM taxes");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}