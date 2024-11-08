<?php
// src/Model/Notification.php

namespace Twetech\Nestogy\Model;

use PDO;

/**
 * Class Notification
 * Handles database operations for user notifications
 * 
 * @package Twetech\Nestogy\Model
 */
class Notification {
    /** @var PDO Database connection instance */
    private $pdo;

    /**
     * Notification constructor
     * 
     * @param PDO $pdo Database connection instance
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves all notifications for a specific user
     * 
     * @param int $user_id The ID of the user
     * @return array Array of notifications as associative arrays
     */
    public function getNotifications($user_id) {
        $query = $this->pdo->query('SELECT * FROM notifications WHERE user_id = :user_id');
        $query->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
