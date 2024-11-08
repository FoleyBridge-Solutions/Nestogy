<?php
// src/Moel/Contact.php

namespace Twetech\Nestogy\Model;

use PDO;

/**
 * Contact Model Class
 * 
 * Handles database operations for contact-related functionality
 */
class Contact
{
    /** @var PDO */
    private $pdo;

    /**
     * Constructor
     *
     * @param PDO $pdo PDO database connection instance
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves all contacts for a given client
     *
     * @param int $client_id The ID of the client
     * @return array Array of contact records
     */
    public function getContacts($client_id)
    {
        $stmt = $this->pdo->prepare("SELECT  * FROM contacts WHERE contact_client_id = :client_id");
        $stmt->execute(['client_id' => $client_id]);
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a specific contact by ID
     *
     * @param int $contact_id The ID of the contact
     * @return array|false Contact record or false if not found
     */
    public function getContact($contact_id)
    {
        $stmt = $this->pdo->prepare("SELECT  * FROM contacts WHERE contact_id = :contact_id");
        $stmt->execute(['contact_id' => $contact_id]);
        return $stmt->fetch();
    }

    /**
     * Retrieves the primary contact for a given client
     *
     * @param int $client_id The ID of the client
     * @return array|false Primary contact record or false if not found
     */
    public function getPrimaryContact($client_id)
    {
        $stmt = $this->pdo->prepare("SELECT  * FROM contacts WHERE contact_client_id = :client_id AND contact_primary = 1");
        $stmt->execute(['client_id' => $client_id]);
        return $stmt->fetch();
    }

    /**
     * Retrieves the most recent ticket for a given contact
     *
     * @param int $contact_id The ID of the contact
     * @return array|false Latest ticket record or false if not found
     */
    public function getContactLastTicket($contact_id)
    {
        $stmt = $this->pdo->prepare("SELECT  * FROM tickets WHERE ticket_contact_id = :contact_id ORDER BY ticket_created_at DESC LIMIT 1");
        $stmt->execute(['contact_id' => $contact_id]);
        return $stmt->fetch();
    }
}