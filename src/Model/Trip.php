<?php
// src/Model/Documentation.php

namespace Twetech\Nestogy\Model;

use PDO;

/**
 * Trip Model Class
 * 
 * Handles database operations for trips
 */
class Trip {
    /** @var PDO Database connection instance */
    private $pdo;

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection instance
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieve trips from the database
     *
     * @param int|false $client_id Optional client ID to filter trips
     * @return array Array of trips as associative arrays
     */
    public function getTrips($client_id = false) {
        if ($client_id) {
            $sql = "SELECT * FROM trips WHERE trip_client_id = :client_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        } else {
            $sql = "SELECT * FROM trips";
            $stmt = $this->pdo->prepare($sql);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}