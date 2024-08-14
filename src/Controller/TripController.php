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

        if (isset($client_id)) {
            // Check if user has access to the client
            if (!$this->auth->checkClientAccess($_SESSION['user_id'], $client_id, 'view')) {
                // If user does not have access, display an error message
                $this->view->error([
                    'title' => 'Access Denied',
                    'message' => 'You do not have permission to view this client.'
                ]);
                return;
            }
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