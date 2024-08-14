<?php
// src/Model/Documentation.php

namespace Twetech\Nestogy\Model;

use PDO;

class Trip {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

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