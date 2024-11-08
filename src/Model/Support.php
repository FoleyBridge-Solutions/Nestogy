<?php
// src/Model/Support.php

namespace Twetech\Nestogy\Model;

use PDO;

/**
 * Support ticket management class
 */
class Support {
    /** @var PDO Database connection */
    private $pdo;

    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection instance
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieve tickets based on various filters
     * 
     * @param string  $status      Ticket status ('open' or 'closed')
     * @param int|bool $client_id  Optional client ID filter
     * @param int|bool $user_id    Optional user ID filter
     * @param string  $ticket_type Type of ticket (default: 'support')
     * 
     * @return array Array of ticket records
     */
    public function getTickets($status = "open", $client_id = false, $user_id = false, $ticket_type = 'support') {
        if ($status == "closed") {
            $status = 5;
            $status_snippet = "AND ticket_status = 5";
        } else {
            $status = 1;
            $status_snippet = "AND ticket_status != 5";
        }
        if ($client_id) {
            if ($user_id) {
                $stmt = $this->pdo->prepare(
                    'SELECT *, (SELECT  ticket_reply_created_at FROM ticket_replies WHERE ticket_reply_ticket_id = tickets.ticket_id ORDER BY ticket_reply_created_at DESC LIMIT 1) AS ticket_last_response FROM tickets
                    LEFT JOIN clients ON tickets.ticket_client_id = clients.client_id
                    LEFT JOIN users ON tickets.ticket_assigned_to = users.user_id
                    LEFT JOIN ticket_statuses ON tickets.ticket_status = ticket_statuses.ticket_status_id
                    LEFT JOIN contacts ON tickets.ticket_contact_id = contacts.contact_id
                    WHERE ticket_client_id = :client_id
                    '.$status_snippet.'
                    AND ticket_assigned_to = :user_id
                    AND ticket_type = :ticket_type
                    ORDER BY ticket_created_at DESC
                ');
                $stmt->execute(['client_id' => $client_id, 'user_id' => $user_id, 'ticket_type' => $ticket_type]);
            } else {
                $stmt = $this->pdo->prepare(
                    'SELECT  *, (SELECT ticket_reply_created_at FROM ticket_replies WHERE ticket_reply_ticket_id = tickets.ticket_id ORDER BY ticket_reply_created_at DESC LIMIT 1) AS ticket_last_response FROM tickets
                    LEFT JOIN clients ON tickets.ticket_client_id = clients.client_id
                    LEFT JOIN users ON tickets.ticket_assigned_to = users.user_id
                    LEFT JOIN ticket_statuses ON tickets.ticket_status = ticket_statuses.ticket_status_id
                    LEFT JOIN contacts ON tickets.ticket_contact_id = contacts.contact_id
                    WHERE ticket_client_id = :client_id
                    '.$status_snippet.'
                    AND ticket_type = :ticket_type
                    ORDER BY ticket_created_at DESC
                ');
                $stmt->execute(['client_id' => $client_id, 'ticket_type' => $ticket_type]);
            }
            $tickets = $stmt->fetchAll();
            foreach ($tickets as $key => $ticket) {
                $tickets[$key]['ticket_last_response'] = $this->getLastResponse($ticket['ticket_id']);
            }
            return $tickets;
        } else {
            if ($user_id) {
                $stmt = $this->pdo->prepare(
                    'SELECT  *, (SELECT ticket_reply_created_at FROM ticket_replies WHERE ticket_reply_ticket_id = tickets.ticket_id ORDER BY ticket_reply_created_at DESC LIMIT 1) AS ticket_last_response FROM tickets
                    LEFT JOIN clients ON tickets.ticket_client_id = clients.client_id
                    LEFT JOIN users ON tickets.ticket_assigned_to = users.user_id
                    LEFT JOIN ticket_statuses ON tickets.ticket_status = ticket_statuses.ticket_status_id
                    LEFT JOIN contacts ON tickets.ticket_contact_id = contacts.contact_id
                    WHERE  ticket_assigned_to = :user_id
                    '.$status_snippet.'
                    AND ticket_type = :ticket_type
                    ORDER BY ticket_created_at DESC
                ');
                $stmt->execute(['user_id' => $user_id, 'ticket_type' => $ticket_type]);
            } else {
                $stmt = $this->pdo->prepare(
                    'SELECT  *, (SELECT ticket_reply_created_at FROM ticket_replies WHERE ticket_reply_ticket_id = tickets.ticket_id ORDER BY ticket_reply_created_at DESC LIMIT 1) AS ticket_last_response FROM tickets
                    LEFT JOIN clients ON tickets.ticket_client_id = clients.client_id
                    LEFT JOIN users ON tickets.ticket_assigned_to = users.user_id
                    LEFT JOIN ticket_statuses ON tickets.ticket_status = ticket_statuses.ticket_status_id
                    LEFT JOIN contacts ON tickets.ticket_contact_id = contacts.contact_id
                    WHERE ticket_type = :ticket_type
                    '.$status_snippet.'
                    ORDER BY ticket_created_at DESC
                ');
                $stmt->execute(['ticket_type' => $ticket_type]);
            }
            $tickets = $stmt->fetchAll();
            return $tickets;
        }
    }

    /**
     * Get all closed tickets
     * 
     * @return array Array of closed ticket records
     */
    public function getClosedTickets() {
        return $this->getTickets('closed');
    }

    /**
     * Get aging tickets that haven't been updated in over 24 hours
     * 
     * @return array Array of aging ticket records
     */
    public function getAgingTickets() {
        $stmt = $this->pdo->prepare('SELECT  *, (SELECT ticket_reply_created_at FROM ticket_replies WHERE ticket_reply_ticket_id = tickets.ticket_id ORDER BY ticket_reply_created_at DESC LIMIT 1) AS ticket_last_response FROM tickets
        LEFT JOIN clients ON tickets.ticket_client_id = clients.client_id
        LEFT JOIN users ON tickets.ticket_assigned_to = users.user_id
        LEFT JOIN ticket_statuses ON tickets.ticket_status = ticket_statuses.ticket_status_id
        LEFT JOIN contacts ON tickets.ticket_contact_id = contacts.contact_id
        WHERE ticket_status != 5
        AND ticket_created_at  < DATE_SUB(NOW(), INTERVAL 1 DAY)
        AND ticket_reply_created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
        AND ticket_type = :ticket_type
        ORDER BY ticket_created_at ASC
        ');
        $stmt->execute(['ticket_type' => 'support']);
        return $stmt->fetchAll();
    }

    /**
     * Get the last response timestamp for a ticket
     * 
     * @param int $ticket_id Ticket ID
     * @return string|false Last response timestamp or false if none found
     */
    private function getLastResponse($ticket_id) {
        $stmt = $this->pdo->prepare('SELECT  ticket_reply_created_at FROM ticket_replies WHERE ticket_reply_ticket_id = :ticket_id ORDER BY ticket_reply_created_at DESC LIMIT 1');
        $stmt->execute(['ticket_id' => $ticket_id]);
        return $stmt->fetchColumn();
    }

    /**
     * Get summary numbers for support header
     * 
     * @param int|bool $client_id Optional client ID filter
     * @return array Array containing counts of different ticket states
     */
    public function getSupportHeaderNumbers($client_id = false) {
        return [
            'open_tickets' => $this->getTotalTicketsOpen($client_id)['total_tickets_open'],
            'closed_tickets' => $this->getTotalTicketsClosed($client_id)['total_tickets_closed'],
            'unassigned_tickets' => $this->getTotalTicketsUnassigned($client_id)['total_tickets_unassigned'],
            'scheduled_tickets' => $this->getTotalRecurringTickets($client_id)['total_scheduled_tickets']
        ];
    }

    /**
     * Get client header
     * 
     * @param int $client_id Client ID
     * @return array Array containing counts of different ticket states
     */
    public function getClientHeader($client_id) {
        return [
            'open_tickets' => $this->getTotalTicketsOpen($client_id),
            'closed_tickets' => $this->getTotalTicketsClosed($client_id)
        ];
    }

    /**
     * Get total tickets open
     * 
     * @param int|bool $client_id Optional client ID filter
     * @return array Array containing total tickets open
     */
    private function getTotalTicketsOpen($client_id = false) {
        if ($client_id) {
            $stmt = $this->pdo->prepare('SELECT  COUNT(ticket_id) AS total_tickets_open FROM tickets WHERE ticket_status = :status AND ticket_client_id = :client_id');
            $stmt->execute(['status' => 1, 'client_id' => $client_id]);
            return $stmt->fetch();
        } else {
            $stmt = $this->pdo->prepare('SELECT  COUNT(ticket_id) AS total_tickets_open FROM tickets WHERE ticket_status = :status');
            $stmt->execute(['status' => 1]);
            return $stmt->fetch();
        }
    }

    /**
     * Get total tickets closed
     * 
     * @param int|bool $client_id Optional client ID filter
     * @return array Array containing total tickets closed
     */
    private function getTotalTicketsClosed($client_id = false) {
        if ($client_id) {
            $stmt = $this->pdo->prepare('SELECT  COUNT(ticket_id) AS total_tickets_closed FROM tickets WHERE ticket_status = :status AND ticket_client_id = :client_id');
            $stmt->execute(['status' => 5, 'client_id' => $client_id]);
            return $stmt->fetch();
        } else {
            $stmt = $this->pdo->prepare('SELECT  COUNT(ticket_id) AS total_tickets_closed FROM tickets WHERE ticket_status = :status');
            $stmt->execute(['status' => 5]);
            return $stmt->fetch();
        }
    }

    /**
     * Get total tickets unassigned
     * 
     * @param int|bool $client_id Optional client ID filter
     * @return array Array containing total tickets unassigned
     */
    private function getTotalTicketsUnassigned($client_id = false) {
        if ($client_id) {
            $stmt = $this->pdo->prepare('SELECT  COUNT(ticket_id) AS total_tickets_unassigned FROM tickets WHERE ticket_status = :status AND ticket_client_id = :client_id');
            $stmt->execute(['status' => 1, 'client_id' => $client_id]);
            return $stmt->fetch();
        } else {
            $stmt = $this->pdo->prepare('SELECT  COUNT(ticket_id) AS total_tickets_unassigned FROM tickets WHERE ticket_status = :status');
            $stmt->execute(['status' => 1]);
            return $stmt->fetch();
        }
    }

    /**
     * Get total recurring tickets
     * 
     * @param int|bool $client_id Optional client ID filter
     * @return array Array containing total recurring tickets
     */
    private function getTotalRecurringTickets($client_id = false) {
        if ($client_id) {
            $stmt = $this->pdo->prepare('SELECT  COUNT(scheduled_ticket_id) AS total_scheduled_tickets FROM scheduled_tickets WHERE scheduled_ticket_client_id = :client_id');
            $stmt->execute(['client_id' => $client_id]);
            return $stmt->fetch();
        } else {
            $stmt = $this->pdo->prepare('SELECT  COUNT(scheduled_ticket_id) AS total_scheduled_tickets FROM scheduled_tickets');
            $stmt->execute();
            return $stmt->fetch();
        }
    }

    /**
     * Get a ticket
     * 
     * @param int $ticket_id Ticket ID
     * @return array|false Ticket record or false if none found
     */
    public function getTicket($ticket_id) {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM tickets
            LEFT JOIN clients ON tickets.ticket_client_id = clients.client_id
            LEFT JOIN users ON tickets.ticket_assigned_to = users.user_id
            LEFT JOIN ticket_statuses ON tickets.ticket_status = ticket_statuses.ticket_status_id
            LEFT JOIN contacts ON tickets.ticket_contact_id = contacts.contact_id
            LEFT JOIN locations ON contacts.contact_location_id = locations.location_id
            WHERE ticket_id = :ticket_id
        ');
        $stmt->execute(['ticket_id' => $ticket_id]);
        return $stmt->fetch();
    }

    /**
     * Get ticket replies
     * 
     * @param int $ticket_id Ticket ID
     * @return array|false Ticket replies or false if none found
     */
    public function getTicketReplies($ticket_id) {
        $stmt = $this->pdo->prepare(
            'SELECT  * FROM ticket_replies
            LEFT JOIN users ON ticket_replies.ticket_reply_by = users.user_id
            WHERE ticket_reply_ticket_id = :ticket_id
            ORDER BY ticket_reply_created_at ASC
        ');
        $stmt->execute(['ticket_id' => $ticket_id]);
        return $stmt->fetchAll();
    }

    /**
     * Get ticket collaborators
     * 
     * @param int $ticket_id Ticket ID
     * @return array Array of collaborators
     */
    public function getTicketCollaborators($ticket_id) {
        $ticket_replies = $this->getTicketReplies($ticket_id);
        $collaborators = [];
        foreach ($ticket_replies as $reply) {
            if (!in_array($reply['user_name'], $collaborators)) {
                $collaborators[] = $reply['user_name'];
            }
        }
        return $collaborators;
    }

    /**
     * Get ticket total reply time
     * 
     * @param int $ticket_id Ticket ID
     * @return string|false Ticket total reply time or false if none found
     */
    public function getTicketTotalReplyTime($ticket_id) {
        $stmt = $this->pdo->prepare('SELECT  SEC_TO_TIME(SUM(TIME_TO_SEC(ticket_reply_time_worked))) AS ticket_total_reply_time FROM ticket_replies WHERE ticket_reply_archived_at IS NULL AND ticket_reply_ticket_id = :ticket_id');
        $stmt->execute(['ticket_id' => $ticket_id]);
        return $stmt->fetch()['ticket_total_reply_time'];
    }

    /**
     * Get unassigned tickets
     * 
     * @param int $month Month
     * @param int $year Year
     * @return int|false Number of unassigned tickets or false if none found
     */
    public function getUnassignedTickets($month, $year) {
        $stmt = $this->pdo->prepare('SELECT  COUNT(ticket_id) AS unassigned_tickets FROM tickets WHERE ticket_status != :status AND ticket_assigned_to = 0 AND MONTH(ticket_created_at) = :month AND YEAR(ticket_created_at) = :year');
        $stmt->execute(['status' => 5, 'month' => $month, 'year' => $year]);
        return $stmt->fetch()['unassigned_tickets'];
    }

    /**
     * Get assigned tickets
     * 
     * @param int $month Month
     * @param int $year Year
     * @param int|null $user_id Optional user ID filter
     * @return int|false Number of assigned tickets or false if none found
     */
    public function getAssignedTickets($month, $year, $user_id = null) {
        if ($user_id == null) {
            $user_id = $_SESSION['user_id'];    
        }
        $stmt = $this->pdo->prepare('SELECT  COUNT(ticket_id) AS assigned_tickets FROM tickets WHERE ticket_status != :status AND ticket_assigned_to = :user_id AND MONTH(ticket_created_at) = :month AND YEAR(ticket_created_at) = :year');
        $stmt->execute(['status' => 5, 'user_id' => $user_id, 'month' => $month, 'year' => $year]);
        return $stmt->fetch()['assigned_tickets'];
    }

    /**
     * Get resolved tickets
     * 
     * @param int $month Month
     * @param int $year Year
     * @param int|null $user_id Optional user ID filter
     * @return int|false Number of resolved tickets or false if none found
     */
    public function getResolvedTickets($month, $year, $user_id = null) {
        if ($user_id == null) {
            $user_id = $_SESSION['user_id'];    
        }
        $stmt = $this->pdo->prepare('SELECT  COUNT(ticket_id) AS resolved_tickets FROM tickets WHERE ticket_status = :status AND ticket_assigned_to = :user_id AND MONTH(ticket_created_at) = :month AND YEAR(ticket_created_at) = :year');
        $stmt->execute(['status' => 5, 'user_id' => $user_id, 'month' => $month, 'year' => $year]);
        return $stmt->fetch()['resolved_tickets'];
    }

    /**
     * Get sales meetings
     * 
     * @return array Array of sales meetings
     */
    public function getSalesMeetings(){
        return [];
    }
}
