<?php
// src/Controller/ProjectController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\Project;
use Twetech\Nestogy\Model\Client;

/**
 * Project Controller
 * 
 * Handles all project-related operations and views
 */
class ProjectController {
    /** @var \PDO */
    private $pdo;
    
    /** @var \Twetech\Nestogy\View\View */
    private $view;
    
    /** @var \Twetech\Nestogy\Auth\Auth */
    private $auth;
    
    /** @var \Twetech\Nestogy\Model\Project */
    private $project;
    
    /** @var \Twetech\Nestogy\Model\Client */
    private $client;

    /**
     * Initialize the Project Controller
     *
     * @param \PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->view = new View();
        $this->auth = new Auth($pdo);
        $this->project = new Project($pdo);
        $this->client = new Client($pdo);
    }

    /**
     * Display list of projects, optionally filtered by client
     *
     * @param int|false $client_id Client ID to filter projects by, or false for all projects
     * @return void
     */
    public function index($client_id = false) {
        $projects = $this->project->getProjects($client_id);
        $data['projects'] = $projects;

        if ($client_id) {
            $this->clientAccessed($client_id);
            $client_page = true;
            $client_header = $this->client->getClientHeader($client_id);
            $data['client_header'] = $client_header['client_header'];
            $data['return_page'] = [
                'name' => 'Clients',
                'link' => 'clients'
            ];
        } else {
            $client_page = false;
            $data['return_page'] = [
                'name' => 'Projects',
                'link' => 'projects'
            ];
        }
        $this->view->render('projects/index', $data, $client_page);
    }

    /**
     * Update client's last accessed timestamp
     *
     * @param int $client_id The ID of the client being accessed
     * @return void
     */
    private function clientAccessed($client_id) {
        $this->client->clientAccessed($client_id);
    }

    /**
     * Display detailed view of a specific project
     *
     * @param int $project_id The ID of the project to display
     * @return void
     */
    public function show($project_id) {
        $project = $this->project->getProject($project_id);
        $tickets = $this->project->getProjectTickets($project_id);
        $tasks = $this->project->getProjectTasks($project_id);
        $project_timeline = $this->project->getProjectTimeline($project_id);
        $this->view->render('projects/show', ['project' => $project, 'tickets' => $tickets, 'tasks' => $tasks, 'project_timeline' => $project_timeline]);
    }
}