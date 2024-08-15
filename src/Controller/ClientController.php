<?php
// src/Controller/ClientController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\Model\Client;
use Twetech\Nestogy\Model\Contact;
use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;

class ClientController {
    private $pdo;
    private $clientModel;
    private $accountingModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        $this->clientModel = new Client($this->pdo);
        $this->accountingModel = new Accounting($this->pdo);

        if (!Auth::check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
    }

    public function index() {
        $view = new View();
        $auth = new Auth($this->pdo);
        // Check if user has access to the client class
        if (!$auth->checkClassAccess($_SESSION['user_id'], 'client', 'view')) {
        // If user does not have access, display an error message
            $view->error([
                'title' => 'Access Denied',
                'message' => 'You do not have permission to view clients.'
            ]);
            return;
        }
        $clients = $this->clientModel->getClients(true);
        // Add Additional Data for Each Client
        foreach ($clients as &$client) {
            $client['client_balance'] = $this->accountingModel->getClientBalance($client['client_id']);
            $client['client_payments'] = $this->accountingModel->getClientPaidAmount($client['client_id']);
            $client['client_recurring_monthly'] = $this->accountingModel->getMonthlySubscriptionAmount($client['client_id']);
        }
        $view->render('clients', ['clients' => $clients]);
    }
    public function show($client_id) {
        $view = new View();
        $auth = new Auth($this->pdo);

        $this->clientAccessed($client_id);
        
        // If client_id is not an integer, display an error message
        if (!is_numeric($client_id)) {
            $view->error([
                'title' => 'Invalid Client ID',
                'message' => 'The client ID must be an integer.'
            ]);
            return;
        }

        // Check if user has access to the client class
        if (!$auth->checkClassAccess($_SESSION['user_id'], 'client', 'view') || !$auth->checkClientAccess($_SESSION['user_id'], $client_id, 'view')) {
            // If user does not have access, display an error message
            $view->error([
                'title' => 'Access Denied',
                'message' => 'You do not have permission to view this client.'
            ]);
            return;
        }

        $clientModel = new Client($this->pdo);
        $client = $clientModel->getClient($client_id);

        $contactModel = new Contact($this->pdo);
        $client['client_contacts'] = $contactModel->getContacts($client_id);

        $data = [
            'client' => $client,
            'client_header' => $clientModel->getClientHeader($client_id)['client_header'],
            'return_page' => [
                'name' => 'Clients',
                'link' => 'clients'
            ]
        ];

        $view->render('client', $data, true);
    }
    public function showContacts($client_id) {
        $contactModel = new Contact($this->pdo);
        $clientModel = new Client($this->pdo);
        $auth = new Auth($this->pdo);
        $view = new View();

        // Check if user has access to the client class
        if (!$auth->checkClassAccess($_SESSION['user_id'], 'contact', 'view')) {
            // If user does not have access, display an error message
            $view->error([
                'title' => 'Access Denied',
                'message' => 'You do not have permission to view client contacts.'
            ]);
            return;
        }
        // Check if user has access to client
        if (!$auth->checkClientAccess($_SESSION['user_id'], $client_id, 'view')) {
            // If user does not have access, display an error message
            $view->error([
                'title' => 'Access Denied',
                'message' => 'You do not have permission to view this client\'s contacts.'
            ]);
            return;
        }

        $rawContacts = $contactModel->getContacts($client_id);

        $contacts = [];
        foreach ($rawContacts as $contact) {
            $contacts[] = [
                '<a href="#" class="dropdown-item loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="client_contact_edit_modal.php?contact_id=' . $contact['contact_id'] . '">
                    ' . $contact['contact_name'] . '
                </a>',
                $contact['contact_email'],
                $contact['contact_phone'],
                $contact['contact_mobile'],
                $contact['contact_primary'] ? 'Yes' : 'No'
            ];
        }
        $data = [
            'card' => [
                'title' => 'Contacts'
            ],
            'client_header' => $clientModel->getClientHeader($client_id)['client_header'],
            'table' => [
                'header_rows' => ['Name', 'Email', 'Phone', 'Mobile', 'Primary'],
                'body_rows' => $contacts
            ],
            'return_page' => [
                'name' => 'Clients',
                'link' => 'clients'
            ]
        ];
        $view->render('simpleTable', $data, true);
    }
    public function showLocations($client_id) {
        $clientModel = new Client($this->pdo);
        $auth = new Auth($this->pdo);
        $view = new View();

        // Check if user has access to the client class
        if (!$auth->checkClassAccess($_SESSION['user_id'], 'client', 'view')) {
            // If user does not have access, display an error message
            $view->error([
                'title' => 'Access Denied',
                'message' => 'You do not have permission to view client locations.'
            ]);
            return;
        }
        // Check if user has access to client
        if (!$auth->checkClientAccess($_SESSION['user_id'], $client_id, 'view')) {
            // If user does not have access, display an error message
            $view->error([
                'title' => 'Access Denied',
                'message' => 'You do not have permission to view this client\'s locations.'
            ]);
            return;
        }
        $rawLocations = $clientModel->getClientLocations($client_id);

        $locations = [];
        foreach ($rawLocations as $location) {
            $locationAdress = $location['location_address'] . ', ' . $location['location_city'] . ', ' . $location['location_state'] . ' ' . $location['location_zip'];
            $locations[] = [
                $location['location_name'],
                $locationAdress,
                $location['location_phone'],
                $location['location_hours']
            ];
        }
        
        $data = [
            'card' => [
                'title' => 'Locations'
            ],
            'client_header' => $clientModel->getClientHeader($client_id)['client_header'],
            'table' => [
                'header_rows' => ['Location Name', 'Address', 'Phone', 'Hours'],
                'body_rows' => $locations
            ],
            'return_page' => [
                'name' => 'Clients',
                'link' => 'clients'
            ]
        ];

        $view->render('simpleTable', $data, true);
    }
    public function clientAccessed($client_id) {
        $clientModel = new Client($this->pdo);
        $clientModel->clientAccessed($client_id);
    }
}
