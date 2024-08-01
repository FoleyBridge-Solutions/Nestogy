<?php
// src/Model/Documentation.php

namespace Twetech\Nestogy\Model;

use PDO;

class Trip {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getTrips() {
        $sql = "SELECT * FROM trips";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}