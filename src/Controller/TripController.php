<?php
// src/Controller/TripController.php


namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Model\Trip;
use Twetech\Nestogy\Model\Client;

/**
 * Trip Controller
 * 
 * Handles all trip-related operations and views
 */
class TripController {
    /** @var \PDO */
    private $pdo;
    
    /** @var \Twetech\Nestogy\View\View */
    private $view;
    
    /** @var \Twetech\Nestogy\Auth\Auth */
    private $auth;
    
    /** @var \Twetech\Nestogy\Model\Trip */
    private $trip;

    /**
     * Initialize TripController with database connection
     *
     * @param \PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->auth = new Auth($this->pdo);
        $this->view = new View();
        $this->trip = new Trip($this->pdo);
        if (!Auth::check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Display list of trips, optionally filtered by client
     *
     * @param int|null $client_id Optional client ID to filter trips
     * @return void
     */
    public function index($client_id = null) {

        $data['card']['title'] = 'Trips';

        if (isset($client_id)) {
            $client_page = true;
            $client = new Client($this->pdo);
            $client_header = $client->getClientHeader($client_id);
            $data['client_header'] = $client_header['client_header'];
            $data['table']['header_rows'] = ['Purpose','Date','Distance','User'];
            $data['action'] = [
                'title' => 'Add Trip',
                'modal' => 'trip_add_modal.php?client_id=' . $client_id
            ];

        } else {
            $client_page = false;
            $data['table']['header_rows'] = ['Client','Purpose','Date','Distance','User'];
            $data['action'] = [
                'title' => 'Add Trip',
                'modal' => 'trip_add_modal.php'
            ];
        }

        $data['table']['body_rows'] = [];
        $trips = $this->trip->getTrips($client_id);
        foreach ($trips as $trip) {
            //find user name
            $username = $this->auth->getUsername($trip['trip_user_id']);

            if ($client_page) {
                $data['table']['body_rows'][] = [
                    $trip['trip_purpose'],
                    $trip['trip_date'],
                    $trip['trip_miles'],
                    $username
                ];
            } else {
                $data['table']['body_rows'][] = [
                    $trip['client_name'],
                    $trip['trip_purpose'],
                    $trip['trip_date'],
                    $trip['trip_miles'],
                    $username
                ];
            }
        }

        error_log('trips: ' . print_r($trips, true));
        $this->view->render('simpleTable', $data, $client_page);
    }
}