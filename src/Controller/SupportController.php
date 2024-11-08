<?php
// src/Controller/SupportController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Model\Support;
use Twetech\Nestogy\Model\Client;

/**
 * Controller handling support ticket functionality.
 *
 * This controller manages the display and interaction with support tickets,
 * including listing tickets, viewing individual tickets, and handling client access.
 *
 * @package Twetech\Nestogy\Controller
 */
class SupportController {
    /** @var \PDO */
    private $pdo;

    /** @var \Twetech\Nestogy\Auth\Auth */
    private $auth;

    /** @var \Twetech\Nestogy\View\View */
    private $view;

    /**
     * Initialize the Support controller.
     *
     * Creates new instances of Auth and View, and checks for user authentication.
     * Redirects to login page if user is not authenticated.
     *
     * @param \PDO $pdo Database connection instance
     * @return void
     * @throws \Exception If database connection fails
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->auth = new Auth($this->pdo);
        $this->view = new View();
        if (!Auth::check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Record client access timestamp.
     *
     * Updates the last accessed timestamp for the specified client.
     *
     * @param int $client_id The ID of the client being accessed
     * @return void
     */
    private function clientAccessed($client_id) {
        $clientModel = new Client($this->pdo);
        $clientModel->clientAccessed($client_id);
    }

    /**
     * Display list of support tickets.
     *
     * Renders a view containing a list of support tickets, filtered by various parameters.
     * If a client ID is provided, additional client-specific information is included.
     *
     * @param int|null    $client_id   Optional client ID to filter tickets
     * @param int|null    $status      Ticket status (5 for closed, null for open)
     * @param int|null    $user_id     Optional user ID to filter tickets
     * @param string|null $ticket_type Type of ticket (defaults to 'support')
     * @return void
     */
    public function index($client_id = null, $status = null, $user_id = null, $ticket_type = null) {

        $supportModel = new Support($this->pdo);
        if ($ticket_type === null) {
            $ticket_type = 'support';
        }

        if ($client_id !== null) {
            $this->clientAccessed($client_id);
            $clientModel = new Client($this->pdo);
            $client = $clientModel->getClient($client_id);
            $client_header = $clientModel->getClientHeader($client_id);
            $data = [
                'tickets' => $status == 5 ? $supportModel->getTickets("closed", $client_id, $user_id, $ticket_type) : $supportModel->getTickets("open", $client_id, $user_id, $ticket_type),
                'client' => $client,
                'client_header' => $client_header['client_header'],
                'client_page' => true,
                'support_header_numbers' => $supportModel->getSupportHeaderNumbers($client_id),
                'return_page' => [
                    'name' => ' All Tickets',
                    'link' => 'tickets'
                ]
            ];
            $this->view->render('tickets', $data, true);
        } else {
            // View all tickets
            $data = [
                'tickets' => $status == 5 ? $supportModel->getTickets("closed", null, $user_id, $ticket_type) : $supportModel->getTickets("open", null, $user_id, $ticket_type),
                'client_page' => false,
                'support_header_numbers' => $supportModel->getSupportHeaderNumbers(),
                'return_page' => [
                    'name' => ' All Tickets',
                    'link' => 'tickets'
                ]
            ];
            $this->view->render('tickets', $data);
        }
    }

    /**
     * Display a specific support ticket.
     *
     * Renders a detailed view of a single support ticket, including replies,
     * collaborators, and related client information if available.
     *
     * @param int $ticket_id The ID of the ticket to display
     * @return void
     * @throws \Exception If ticket is not found
     */
    public function show($ticket_id) {
        $supportModel = new Support($this->pdo);
        $clientModel = new Client($this->pdo);
        $ticket = $supportModel->getTicket($ticket_id);
        $ticket_replies = $supportModel->getTicketReplies($ticket_id);
        $data = [
            'ticket' => $ticket,
            'ticket_replies' => $ticket_replies,
            'num_replies' => count($ticket_replies),
            'ticket_collaborators' => $supportModel->getTicketCollaborators($ticket_id),
            'ticket_total_reply_time' => $supportModel->getTicketTotalReplyTime($ticket_id) ?? '00:00:00',
            'return_page' => [
                'name' => 'All Tickets',
                'link' => 'tickets'
            ]
        ];
        if (!empty($ticket['ticket_client_id'])) {
            $this->clientAccessed($ticket['ticket_client_id']);
            $client_id = $ticket['ticket_client_id'];
            $data['client'] = $clientModel->getClient($client_id);
            $data['client_header'] = $clientModel->getClientHeader($client_id)['client_header'];
            $data['client_page'] = true;
        } else {
            $data['client_page'] = false;
        }
        $this->view->render('ticket', $data, $data['client_page']);
    }
}
