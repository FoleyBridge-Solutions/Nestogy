<?php
// src/Controller/CourseController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\Model\Client;

/**
 * Course Controller
 * 
 * Handles all course-related operations and views
 */
class CourseController {
    /** @var View */
    private $view;

    /**
     * Initialize the course controller
     *
     * @param \PDO $pdo Database connection instance
     */
    public function __construct($pdo) {
        $this->view = new View();
        if (!Auth::check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Display the course index page
     *
     * @return void
     */
    public function index() {
        $data = [];
        $this->view->render('course', $data);
    }
}