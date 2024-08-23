<?php
// src/Model/Support.php

namespace Twetech\Nestogy\Model;

use PDO;
class Support {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    public function getTickets($status = "open", $client_id = false, $user_id = false, $ticket_type = 'support') {
        error_log('Ticket Type: ' . $ticket_type);
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
                    'SELECT *, (SELECT SQL_CACHE ticket_reply_created_at FROM ticket_replies WHERE ticket_reply_ticket_id = tickets.ticket_id ORDER BY ticket_reply_created_at DESC LIMIT 1) AS ticket_last_response FROM tickets
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
                    'SELECT SQL_CACHE *, (SELECT ticket_reply_created_at FROM ticket_replies WHERE ticket_reply_ticket_id = tickets.ticket_id ORDER BY ticket_reply_created_at DESC LIMIT 1) AS ticket_last_response FROM tickets
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
                    'SELECT SQL_CACHE *, (SELECT ticket_reply_created_at FROM ticket_replies WHERE ticket_reply_ticket_id = tickets.ticket_id ORDER BY ticket_reply_created_at DESC LIMIT 1) AS ticket_last_response FROM tickets
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
                    'SELECT SQL_CACHE *, (SELECT ticket_reply_created_at FROM ticket_replies WHERE ticket_reply_ticket_id = tickets.ticket_id ORDER BY ticket_reply_created_at DESC LIMIT 1) AS ticket_last_response FROM tickets
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
    private function getLastResponse($ticket_id) {
        $stmt = $this->pdo->prepare('SELECT SQL_CACHE ticket_reply_created_at FROM ticket_replies WHERE ticket_reply_ticket_id = :ticket_id ORDER BY ticket_reply_created_at DESC LIMIT 1');
        $stmt->execute(['ticket_id' => $ticket_id]);
        return $stmt->fetchColumn();
    }
    public function getSupportHeaderNumbers($client_id = false) {
        return [
            'open_tickets' => $this->getTotalTicketsOpen($client_id)['total_tickets_open'],
            'closed_tickets' => $this->getTotalTicketsClosed($client_id)['total_tickets_closed'],
            'unassigned_tickets' => $this->getTotalTicketsUnassigned($client_id)['total_tickets_unassigned'],
            'scheduled_tickets' => $this->getTotalRecurringTickets($client_id)['total_scheduled_tickets']
        ];
    }
    public function getClientHeader($client_id) {
        return [
            'open_tickets' => $this->getTotalTicketsOpen($client_id),
            'closed_tickets' => $this->getTotalTicketsClosed($client_id)
        ];
    }
    private function getTotalTicketsOpen($client_id = false) {
        if ($client_id) {
            $stmt = $this->pdo->prepare('SELECT SQL_CACHE COUNT(ticket_id) AS total_tickets_open FROM tickets WHERE ticket_status = :status AND ticket_client_id = :client_id');
            $stmt->execute(['status' => 1, 'client_id' => $client_id]);
            return $stmt->fetch();
        } else {
            $stmt = $this->pdo->prepare('SELECT SQL_CACHE COUNT(ticket_id) AS total_tickets_open FROM tickets WHERE ticket_status = :status');
            $stmt->execute(['status' => 1]);
            return $stmt->fetch();
        }
    }
    private function getTotalTicketsClosed($client_id = false) {
        if ($client_id) {
            $stmt = $this->pdo->prepare('SELECT SQL_CACHE COUNT(ticket_id) AS total_tickets_closed FROM tickets WHERE ticket_status = :status AND ticket_client_id = :client_id');
            $stmt->execute(['status' => 5, 'client_id' => $client_id]);
            return $stmt->fetch();
        } else {
            $stmt = $this->pdo->prepare('SELECT SQL_CACHE COUNT(ticket_id) AS total_tickets_closed FROM tickets WHERE ticket_status = :status');
            $stmt->execute(['status' => 5]);
            return $stmt->fetch();
        }
    }
    private function getTotalTicketsUnassigned($client_id = false) {
        if ($client_id) {
            $stmt = $this->pdo->prepare('SELECT SQL_CACHE COUNT(ticket_id) AS total_tickets_unassigned FROM tickets WHERE ticket_status = :status AND ticket_client_id = :client_id');
            $stmt->execute(['status' => 1, 'client_id' => $client_id]);
            return $stmt->fetch();
        } else {
            $stmt = $this->pdo->prepare('SELECT SQL_CACHE COUNT(ticket_id) AS total_tickets_unassigned FROM tickets WHERE ticket_status = :status');
            $stmt->execute(['status' => 1]);
            return $stmt->fetch();
        }
    }
    private function getTotalRecurringTickets($client_id = false) {
        if ($client_id) {
            $stmt = $this->pdo->prepare('SELECT SQL_CACHE COUNT(scheduled_ticket_id) AS total_scheduled_tickets FROM scheduled_tickets WHERE scheduled_ticket_client_id = :client_id');
            $stmt->execute(['client_id' => $client_id]);
            return $stmt->fetch();
        } else {
            $stmt = $this->pdo->prepare('SELECT SQL_CACHE COUNT(scheduled_ticket_id) AS total_scheduled_tickets FROM scheduled_tickets');
            $stmt->execute();
            return $stmt->fetch();
        }
    }
    public function getTicket($ticket_id) {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM tickets
            LEFT JOIN clients ON tickets.ticket_client_id = clients.client_id
            LEFT JOIN users ON tickets.ticket_assigned_to = users.user_id
            LEFT JOIN ticket_statuses ON tickets.ticket_status = ticket_statuses.ticket_status_id
            LEFT JOIN contacts ON tickets.ticket_contact_id = contacts.contact_id
            WHERE ticket_id = :ticket_id
        ');
        $stmt->execute(['ticket_id' => $ticket_id]);
        return $stmt->fetch();
    }
    public function getTicketReplies($ticket_id) {
        $stmt = $this->pdo->prepare(
            'SELECT SQL_CACHE * FROM ticket_replies
            LEFT JOIN users ON ticket_replies.ticket_reply_by = users.user_id
            WHERE ticket_reply_ticket_id = :ticket_id
            ORDER BY ticket_reply_created_at ASC
        ');
        $stmt->execute(['ticket_id' => $ticket_id]);
        return $stmt->fetchAll();
    }
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
    public function getTicketTotalReplyTime($ticket_id) {
        $stmt = $this->pdo->prepare('SELECT SQL_CACHE SEC_TO_TIME(SUM(TIME_TO_SEC(ticket_reply_time_worked))) AS ticket_total_reply_time FROM ticket_replies WHERE ticket_reply_archived_at IS NULL AND ticket_reply_ticket_id = :ticket_id');
        $stmt->execute(['ticket_id' => $ticket_id]);
        return $stmt->fetch()['ticket_total_reply_time'];
    }
}
