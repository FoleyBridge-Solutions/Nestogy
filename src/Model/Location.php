<?php
// src/Model/Location.php

namespace Twetech\Nestogy\Model;

use PDO;

/**
 * Class Location
 * Handles database operations for location entities
 * 
 * @package Twetech\Nestogy\Model
 */
class Location {
    /** @var PDO Database connection */
    private $pdo;

    /**
     * Location constructor
     * 
     * @param PDO $pdo Database connection instance
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves all locations for a given client
     * 
     * @param int $client_id The client identifier
     * @return array Array of location records
     */
    public function getLocations($client_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM locations WHERE location_client_id = $client_id");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retrieves a specific location by its ID
     * 
     * @param int $location_id The location identifier
     * @return array|false Location record or false if not found
     */
    public function getLocation($location_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM locations WHERE location_id = :location_id");
        $stmt->execute(['location_id' => $location_id]);
        return $stmt->fetch();
    }

    /**
     * Retrieves the primary location for a given client
     * 
     * @param int $client_id The client identifier
     * @return array|false Primary location record or false if not found
     */
    public function getPrimaryLocation($client_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM locations WHERE location_client_id = :client_id AND location_primary = 1");
        $stmt->execute(['client_id' => $client_id]);
        return $stmt->fetch();
    }
}