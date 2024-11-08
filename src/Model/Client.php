<?php
// src/Model/Client.php

namespace Twetech\Nestogy\Model;

use PDO;
use Twetech\Nestogy\Model\Support;
use Twetech\Nestogy\Model\Contact;
use Twetech\Nestogy\Model\Location;
use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\Controller\ClientController;

/**
 * Client Model
 * 
 * Handles all client-related database operations and business logic
 */
class Client {
    /** @var PDO */
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
     * Retrieve clients from the database
     * 
     * @param bool $home Whether to fetch detailed information for home page display
     * @return array Array of client records
     */
    public function getClients($home = false) {

        if ($home) {
            $stmt = $this->pdo->query(
                "SELECT SQL_CACHE clients.*, contacts.*, locations.*, GROUP_CONCAT(tags.tag_name) AS tag_names
                FROM clients
                LEFT JOIN contacts ON clients.client_id = contacts.contact_client_id AND contact_primary = 1
                LEFT JOIN locations ON clients.client_id = locations.location_client_id AND location_primary = 1
                LEFT JOIN client_tags ON client_tags.client_tag_client_id = clients.client_id
                LEFT JOIN tags ON tags.tag_id = client_tags.client_tag_tag_id
                WHERE clients.client_archived_at IS NULL
                AND clients.client_lead = 0
                GROUP BY clients.client_id
                ORDER BY clients.client_accessed_at DESC
            ");
            return $stmt->fetchAll();
        } else {
            $stmt = $this->pdo->query("SELECT SQL_CACHE * FROM clients WHERE client_archived_at IS NULL ORDER BY client_name ASC");
            return $stmt->fetchAll();
        }
    } 

    /**
     * Get a single client by ID
     * 
     * @param int $client_id The client's ID
     * @return array|false Client data or false if not found
     */
    public function getClient($client_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM clients WHERE client_id = :client_id");
        $stmt->execute(['client_id' => $client_id]);
        return $stmt->fetch();
    }

    /**
     * Get comprehensive overview of a client
     * 
     * @param int $client_id The client's ID
     * @return array Client overview data including tickets, expirations, and activities
     */
    public function getClientOverview($client_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM clients WHERE client_id = :client_id");
        $stmt->execute(['client_id' => $client_id]);
        $client = $stmt->fetch();

        $data = [];
        $data['client'] = $client;

        $support = new Support($this->pdo);
        $documentation = new Documentation($this->pdo);
        $data['tickets'] = $support->getTickets('open', $client_id);

        $data['expirations'] = $documentation->getExpirations($client_id);

        $data['recent_activities'] = $this->getClientRecentActivities($client_id);

        return $data;
    }

    /**
     * Get client header information
     * 
     * @param int $client_id The client's ID
     * @return array Client header data including support, contact, and location info
     */
    public function getClientHeader($client_id) {
        $client_id = intval($client_id);

        $support = new Support($this->pdo);
        $client_header_support = $support->getClientHeader($client_id);

        $contact = new Contact($this->pdo);
        $client_header_contact = $contact->getPrimaryContact($client_id);

        $location = new Location($this->pdo);
        $client_header_location = $location->getPrimaryLocation($client_id);

        $this->clientAccessed($client_id);

        $accounting = new Accounting($this->pdo);
        $client_header_balance = $accounting->getClientBalance($client_id);
        $client_header_paid = $accounting->getClientPaidAmount($client_id);

        $stmt = $this->pdo->prepare(
            "SELECT * FROM clients WHERE client_id = :client_id"
        );
        $stmt->execute(['client_id' => $client_id]);

        $return = ['client_header' => $stmt->fetch()];
        $return['client_header']['client_balance'] = $client_header_balance;
        $return['client_header']['client_payments'] = $client_header_paid;
        $return['client_header']['client_open_tickets'] = $client_header_support['open_tickets']['total_tickets_open'];
        $return['client_header']['client_closed_tickets'] = $client_header_support['closed_tickets']['total_tickets_closed'];
        $return['client_header']['client_primary_contact'] = $client_header_contact;
        $return['client_header']['client_primary_location'] = $client_header_location;
        

        return $return;

    }

    /**
     * Get client locations
     * 
     * @param int $client_id The client's ID
     * @return array Array of location records
     */
    public function getClientLocations($client_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM locations WHERE location_client_id = :client_id");
        $stmt->execute(['client_id' => $client_id]);
        return $stmt->fetchAll();
    }

    /**
     * Update client accessed timestamp
     * 
     * @param int $client_id The client's ID
     */
    public function clientAccessed($client_id) {
        $stmt = $this->pdo->prepare("UPDATE clients SET client_accessed_at = NOW() WHERE client_id = :client_id");
        $stmt->execute(['client_id' => $client_id]);
    }

    /**
     * Get client contact information
     * 
     * @param int $client_id The client's ID
     * @param string $contact_type The type of contact to retrieve (default: 'primary')
     * @return array|false Client contact data or false if not found
     */
    public function getClientContact($client_id, $contact_type = 'primary') {
        switch ($contact_type) {
            case 'billing':
                $stmt = $this->pdo->prepare("SELECT  * FROM contacts WHERE contact_client_id = :client_id AND contact_billing = 1");
                break;
            case 'primary':
                $stmt = $this->pdo->prepare("SELECT  * FROM contacts WHERE contact_client_id = :client_id AND contact_primary = 1");
                break;
            default:
                $stmt = $this->pdo->prepare("SELECT  * FROM contacts WHERE contact_client_id = :client_id");
                break;
        }
        $stmt->execute(['client_id' => $client_id]);
        return $stmt->fetch();
    }

    /**
     * Get client recent activities
     * 
     * @param int $client_id The client's ID
     * @return array Array of recent activity records
     */
    public function getClientRecentActivities($client_id) {
        $stmt = $this->pdo->prepare("
        SELECT * FROM logs
        WHERE log_client_id = :client_id
        ORDER BY log_created_at DESC
        LIMIT 50
        ");
        $stmt->execute(['client_id' => $client_id]);
        return $stmt->fetchAll();
    }

    /**
     * Get new clients created within a specific month and year
     * 
     * @param int $month The month to search for
     * @param int $year The year to search for
     * @return array Array of new client records
     */
    public function getNewClients($month, $year) {
        $start_date = date('Y-m-01', strtotime($year . '-' . $month . '-01'));
        $end_date = date('Y-m-t', strtotime($year . '-' . $month . '-01'));
        
        $stmt = $this->pdo->prepare("
        SELECT * FROM clients
        WHERE client_created_at >= :start_date AND client_created_at <= :end_date
        ");
        $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
        return $stmt->fetchAll();
    }

    /**
     * Get clients without login
     * 
     * @return array Array of client records without login
     */
    public function getClientsWithoutLogin() {
        $stmt = $this->pdo->prepare("
        SELECT * FROM clients
        LEFT JOIN contacts ON contacts.contact_client_id = clients.client_id
        WHERE client_id NOT IN (SELECT contact_client_id FROM contacts WHERE contact_auth_method IS NOT NULL)
        AND client_archived_at IS NULL
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get clients without subscription
     * 
     * @param int|null $subscription_id The subscription ID to search for (default: null)
     * @return array Array of client records without subscription
     */
    public function clientsWithoutSubscription($subscription_id = null) {
        $stmt = $this->pdo->prepare("
        SELECT * FROM clients
        WHERE client_id NOT IN (SELECT subscription_client_id FROM subscriptions WHERE subscription_id = :subscription_id)
        AND client_archived_at IS NULL
        ");
        $stmt->execute(['subscription_id' => $subscription_id]);
        return $stmt->fetchAll();
    }

    /**
     * Get leads
     * 
     * @return array Array of lead records
     */
    public function getLeads(){
        $stmt = $this->pdo->query("SELECT * FROM clients WHERE client_lead = 1 AND client_archived_at IS NULL ORDER BY client_name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Get qualified leads
     * 
     * @return array Array of qualified lead records
     */
    public function getQualifiedLeads(){
        $stmt = $this->pdo->query("SELECT * FROM clients WHERE client_lead = 1 AND client_qualified_at IS NOT NULL AND client_archived_at IS NULL ORDER BY client_name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Get sales contacts
     * 
     * @return array Empty array (placeholder method)
     */
    public function getSalesContacts(){
        return [];
    }

    /**
     * Get sales landings
     * 
     * @return array Empty array (placeholder method)
     */
    public function getSalesLandings(){
        return [];
    }
}
