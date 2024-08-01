<?php
// src/Controller/SupportController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Model\Support;
use Twetech\Nestogy\Model\Client;

class SupportController {
    private $pdo;
    private $auth;
    private $view;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->auth = new Auth($this->pdo);
        $this->view = new View();
    }

    public function index($client_id = null, $status = null) {
        error_log("SupportController::index called with client_id: $client_id, status: $status");

        $supportModel = new Support($this->pdo);

        // Check if user has access to the support class
        if (!$this->auth->checkClassAccess($_SESSION['user_id'], 'support', 'view')) {
            error_log("Access denied for user_id: {$_SESSION['user_id']} to view support tickets.");
            $this->view->error([
                'title' => 'Access Denied',
                'message' => 'You do not have permission to view support tickets.'
            ]);
            return;
        }

        if ($client_id !== null) {
            // Check if user has access to client
            if (!$this->auth->checkClientAccess($_SESSION['user_id'], $client_id, 'view')) {
                error_log("Access denied for user_id: {$_SESSION['user_id']} to view client_id: $client_id tickets.");
                $this->view->error([
                    'title' => 'Access Denied',
                    'message' => 'You do not have permission to view this client\'s tickets.'
                ]);
                return;
            }

            // Get client details
            $clientModel = new Client($this->pdo);
            $client = $clientModel->getClient($client_id);
            $client_header = $clientModel->getClientHeader($client_id);
            error_log("Client details retrieved for client_id: $client_id");
            error_log("Client header: " . json_encode($client_header));

            // View tickets for that client
            $data = [
                'tickets' => $status == 5 ? $supportModel->getClosedTickets($client_id) : $supportModel->getOpenTickets($client_id),
                'client' => $client,
                'client_header' => $client_header['client_header'], // Ensure correct structure
                'client_page' => true,
                'support_header_numbers' => $supportModel->getSupportHeaderNumbers()
            ];
            error_log("Tickets retrieved for client_id: $client_id, status: $status");
            $this->view->render('tickets', $data, true);
        } else {
            // View all tickets
            $data = [
                'tickets' => $status == 5 ? $supportModel->getClosedTickets() : $supportModel->getOpenTickets(),
                'client_page' => false,
                'support_header_numbers' => $supportModel->getSupportHeaderNumbers()
            ];
            error_log("All tickets retrieved, status: $status");
            $this->view->render('tickets', $data);
        }
    }

    public function show($ticket_id) {
        error_log("SupportController::show called with ticket_id: $ticket_id");

        // Check if user has access to the support class
        if (!$this->auth->checkClassAccess($_SESSION['user_id'], 'support', 'view')) {
            error_log("Access denied for user_id: {$_SESSION['user_id']} to view support tickets.");
            $this->view->error([
                'title' => 'Access Denied',
                'message' => 'You do not have permission to view support tickets.'
            ]);
            return;
        }

        $supportModel = new Support($this->pdo);
        $clientModel = new Client($this->pdo);
        $ticket = $supportModel->getTicket($ticket_id);
        error_log("Ticket details retrieved for ticket_id: $ticket_id");

        $data = [
            'ticket' => $ticket,
            'ticket_replies' => $supportModel->getTicketReplies($ticket_id),
            'ticket_collaborators' => $supportModel->getTicketCollaborators($ticket_id)
        ];

        if (!empty($ticket['ticket_client_id'])) {
            $client_id = $ticket['ticket_client_id'];
            $data['client'] = $clientModel->getClient($client_id);
            $data['client_header'] = $clientModel->getClientHeader($client_id)['client_header'];
            error_log("Client details retrieved for client_id: $client_id associated with ticket_id: $ticket_id");
            $data['client_page'] = true;
        } else {
            $data['client_page'] = false;
        }

        $this->view->render('ticket', $data, $data['client_page']);
    }
}