<?php
// src/Controller/TripController.php


namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Model\Trip;
use Twetech\Nestogy\Model\Client;

class TripController {
    private $pdo;
    private $view;
    private $auth;
    private $trip;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->auth = new Auth($this->pdo);
        $this->view = new View();
        $this->trip = new Trip($this->pdo);
    }

    public function index($client_id = null) {

        $data['card']['title'] = 'Trips';
        $data['table']['header_rows'] = ['Purpose','Date','Distance','User'];
        $data['table']['body_rows'] = [];
        $trips = $this->trip->getTrips();
        foreach ($trips as $trip) {
            //find user name
            $username = $this->auth->getUsername($trip['trip_user_id']);
            $data['table']['body_rows'][] = [$trip['trip_purpose'], $trip['trip_date'], $trip['trip_miles'], $username];
        }

        error_log('trips: ' . print_r($trips, true));
        $this->view->render('simpleTable', $data);
    }
}