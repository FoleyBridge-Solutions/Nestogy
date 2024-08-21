<?php
// src/Model/Client.php

namespace Twetech\Nestogy\Model;

use PDO;
use Twetech\Nestogy\Model\Support;
use Twetech\Nestogy\Model\Contact;
use Twetech\Nestogy\Model\Location;
use Twetech\Nestogy\Model\Accounting;


class Client {
    private $pdo;
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
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
            $stmt = $this->pdo->query("SELECT SQL_CACHE * FROM clients");
            return $stmt->fetchAll();
        }
    }
    public function getClient($client_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM clients WHERE client_id = :client_id");
        $stmt->execute(['client_id' => $client_id]);
        return $stmt->fetch();
    }
    public function getClientHeader($client_id) {
        $client_id = intval($client_id);

        $support = new Support($this->pdo);
        $client_header_support = $support->getClientHeader($client_id);

        $contact = new Contact($this->pdo);
        $client_header_contact = $contact->getPrimaryContact($client_id);

        $location = new Location($this->pdo);
        $client_header_location = $location->getPrimaryLocation($client_id);

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
    public function getClientLocations($client_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM locations WHERE location_client_id = :client_id");
        $stmt->execute(['client_id' => $client_id]);
        return $stmt->fetchAll();
    }
    public function clientAccessed($client_id) {
        $stmt = $this->pdo->prepare("UPDATE clients SET client_accessed_at = NOW() WHERE client_id = :client_id");
        $stmt->execute(['client_id' => $client_id]);
    }
    public function getClientContact($client_id, $contact_type = 'primary') {
        switch ($contact_type) {
            case 'billing':
                $stmt = $this->pdo->prepare("SELECT SQL_CACHE * FROM contacts WHERE contact_client_id = :client_id AND contact_billing = 1");
                break;
            case 'primary':
                $stmt = $this->pdo->prepare("SELECT SQL_CACHE * FROM contacts WHERE contact_client_id = :client_id AND contact_primary = 1");
                break;
            default:
                $stmt = $this->pdo->prepare("SELECT SQL_CACHE * FROM contacts WHERE contact_client_id = :client_id");
                break;
        }
        $stmt->execute(['client_id' => $client_id]);
        return $stmt->fetch();
    }
}
