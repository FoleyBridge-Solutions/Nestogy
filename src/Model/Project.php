<?php
// src/Model/Project.php

namespace Twetech\Nestogy\Model;

use PDO;

/**
 * Project Model Class
 * 
 * Handles all database operations and business logic related to projects
 */
class Project {
    /** @var PDO Database connection instance */
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
     * Retrieves all projects, optionally filtered by client ID
     * 
     * @param int|false $client_id Optional client ID to filter projects
     * @return array List of projects with their associated client and manager information
     */
    public function getProjects($client_id = false) {
        if ($client_id) {
            $stmt = $this->pdo->prepare('SELECT * FROM projects
            LEFT JOIN clients ON projects.project_client_id = clients.client_id
            LEFT JOIN users ON projects.project_manager = users.user_id
            WHERE projects.project_client_id = :client_id
            ORDER BY projects.project_due DESC');
            $stmt->execute(['client_id' => $client_id]);
        } else {
            $stmt = $this->pdo->prepare('SELECT * FROM projects
            LEFT JOIN clients ON projects.project_client_id = clients.client_id
            LEFT JOIN users ON projects.project_manager = users.user_id
            ORDER BY projects.project_due DESC');
            $stmt->execute();
        }
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($projects as &$project) {
            $status_id = floor($this->getProjectStatus($project['project_id']));
            $project['project_status'] = $this->getProjectStatusName($status_id);
        }
        return $projects;
    }

    /**
     * Gets the status name for a given status ID
     * 
     * @param int $status_id The status ID to look up
     * @return string The status name
     */
    public function getProjectStatusName($status_id) {
        $stmt = $this->pdo->prepare('SELECT ticket_status_name FROM ticket_statuses WHERE ticket_status_id = :status_id');
        $stmt->execute(['status_id' => $status_id]);
        return $stmt->fetchColumn();
    }

    /**
     * Retrieves a single project by its ID
     * 
     * @param int $project_id The project ID to retrieve
     * @return array Project details including client and manager information
     */
    public function getProject($project_id) {
        $stmt = $this->pdo->prepare('SELECT * FROM projects
        LEFT JOIN clients ON projects.project_client_id = clients.client_id
        LEFT JOIN users ON projects.project_manager = users.user_id
        WHERE projects.project_id = :project_id');
        $stmt->execute(['project_id' => $project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        $project['project_status'] = $this->getProjectStatusName($this->getProjectStatus($project_id));
        return $project;
    }

    /**
     * Gets all tickets associated with a project
     * 
     * @param int $project_id The project ID to get tickets for
     * @return array List of tickets with their status information
     */
    public function getProjectTickets($project_id) {
        $stmt = $this->pdo->prepare('SELECT * FROM tickets
        LEFT JOIN ticket_statuses ON tickets.ticket_status = ticket_statuses.ticket_status_id
        WHERE ticket_project_id = :project_id');
        $stmt->execute(['project_id' => $project_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculates the average status of all tickets in a project
     * 
     * @param int $project_id The project ID to calculate status for
     * @return float Average status value (0 if no tickets exist)
     */
    public function getProjectStatus($project_id) {
        $tickets = $this->getProjectTickets($project_id);
        // get the average of the ticket statuses
        $statuses = array_column($tickets, 'ticket_status');
        if (empty($statuses)) {
            return 0;
        }
        return array_sum($statuses) / count($statuses);
    }

    /**
     * Retrieves all tasks associated with a project
     * 
     * @param int $project_id The project ID to get tasks for
     * @return array List of tasks with their associated ticket information
     */
    public function getProjectTasks($project_id) {
        $stmt = $this->pdo->prepare('SELECT * FROM tasks LEFT JOIN tickets ON tasks.task_ticket_id = tickets.ticket_id
        WHERE ticket_project_id = :project_id OR task_project_id = :project_id2');
        $stmt->execute(['project_id' => $project_id, 'project_id2' => $project_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets the project timeline by combining ticket replies and project notes
     * 
     * @param int $project_id The project ID to get timeline for
     * @return array Combined and sorted list of ticket replies and project notes
     */
    public function getProjectTimeline($project_id) {
        // Get all ticket replies, and all project notes
        // Order by date

        $stmt = $this->pdo->prepare('SELECT * FROM ticket_replies
        LEFT JOIN tickets ON ticket_replies.ticket_reply_ticket_id = tickets.ticket_id
        LEFT JOIN users ON ticket_replies.ticket_reply_by = users.user_id
        WHERE ticket_project_id = :project_id ORDER BY ticket_reply_created_at DESC');
        $stmt->execute(['project_id' => $project_id]);
        $ticket_replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare('SELECT * FROM project_notes
        LEFT JOIN users ON project_notes.project_note_created_by = users.user_id
        WHERE project_note_project_id = :project_id ORDER BY project_note_date DESC');
        $stmt->execute(['project_id' => $project_id]);
        $project_notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Merge the two arrays
        $timeline = array_merge($ticket_replies, $project_notes);

        // Sort by date
        usort($timeline, function($a, $b) {
            return strtotime($a['ticket_reply_created_at']) - strtotime($b['ticket_reply_created_at']);
        });

        return $timeline;
    }
}