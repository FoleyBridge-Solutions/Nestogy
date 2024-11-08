<?php
// src/Controller/HomeController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\Model\Client;
use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;

/**
 * HomeController handles the main homepage functionality
 */
class HomeController {
    /**
     * Display the homepage
     * 
     * Checks for user authentication and renders the home view
     * If user is not authenticated, redirects to login page
     * 
     * @return void
     */
    public function index() {
        if (!Auth::check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
        $view = new View();
        $view->render('home');
    }
}
