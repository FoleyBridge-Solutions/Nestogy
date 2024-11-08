<?php
// src/Controller/InventoryController.php

namespace Twetech\Nestogy\Controller;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\Model\Inventory;
use NumberFormatter;

/**
 * Controller for handling inventory-related operations
 */
class InventoryController {
    /** @var \PDO */
    private $pdo;

    /** @var View */
    private $view;

    /** @var Auth */
    private $auth;

    /** @var Inventory */
    private $inventory;

    /**
     * Initialize the Inventory Controller
     *
     * @param \PDO $pdo Database connection instance
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->view = new View();
        $this->auth = new Auth($pdo);
        $this->inventory = new Inventory($pdo);
        if (!Auth::check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Display the inventory index page
     *
     * @param int|null $location_id Optional location ID to filter inventory items
     * @return void
     */
    public function index($location_id = null) {
        $data = [
            'locations' => $this->inventory->getLocations(),
            'items' => $this->inventory->getItems(),
            'categories' => $this->inventory->getCategories()
        ];
        
        $this->view->render('inventory/index', $data);
    }
}