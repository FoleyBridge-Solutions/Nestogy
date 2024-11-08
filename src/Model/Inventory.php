<?php
// src/Model/Accounting.php


namespace Twetech\Nestogy\Model;

use PDO;

/**
 * Class Inventory
 * Manages inventory-related operations including locations, items, and asset tag generation
 * 
 * @package Twetech\Nestogy\Model
 */
class Inventory {
    /** @var PDO Database connection instance */
    private $pdo;

    /**
     * Inventory constructor
     * 
     * @param PDO $pdo Database connection instance
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves all inventory locations with associated user information
     * 
     * @return array Array of inventory locations with user details
     */
    public function getLocations() {
        $stmt = $this->pdo->prepare("SELECT * FROM inventory_locations
        LEFT JOIN users ON inventory_locations.inventory_location_user_id = users.user_id");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all inventory items with associated product and location information
     * Generates asset tags for items that don't have one
     * 
     * @return array Array of inventory items with complete details
     */
    public function getItems() {
        $stmt = $this->pdo->prepare("SELECT * FROM inventory
        LEFT JOIN products ON inventory.inventory_product_id = products.product_id
        LEFT JOIN inventory_locations ON inventory.inventory_location_id = inventory_locations.inventory_location_id
        ");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as &$item) {
            if ($item['inventory_asset_tag'] == '0') {
                $item['inventory_asset_tag'] = $this->generateAssetTag(
                    $item['inventory_id'], 
                    $item['inventory_client_id'] ?? null, 
                    $item['inventory_product_id'] ?? null, 
                    $item['inventory_location_id'] ?? null, 
                    $item['inventory_serial_number'] ?? null
                );
            }
        }
        return $items;
    }

    /**
     * Retrieves all available categories
     * 
     * @return array Array of categories
     */
    public function getCategories() {
        $stmt = $this->pdo->prepare("SELECT * FROM categories");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generates a unique asset tag for an inventory item
     * 
     * @param int      $inventory_id  The ID of the inventory item
     * @param int|null $client_id     The client ID (optional)
     * @param int|null $product_id    The product ID (optional)
     * @param int|null $location_id   The location ID (optional)
     * @param string|null $serial_number The serial number (optional)
     * @param int      $attempts      Number of generation attempts (used for recursion)
     * 
     * @return string Generated unique asset tag
     * @throws \RuntimeException When unable to generate a unique tag after 10 attempts
     */
    private function generateAssetTag($inventory_id, $client_id = null, $product_id = null, $location_id = null, $serial_number = null, $attempts = 0) {
        if ($attempts > 10) {
            throw new \RuntimeException('Unable to generate unique asset tag after 10 attempts');
        }

        if ($serial_number) {
            $asset_tag = preg_replace('/[^A-Z0-9]/', '', strtoupper($serial_number));
        } else {
            // Ensure values are at least 0 if null
            $product_id = max(1, intval($product_id));
            $client_id = ($client_id !== null && $client_id !== '') ? intval($client_id) : 0;
            $location_id = max(1, intval($location_id));

            // If NO client (client_id = 0), use the simple format
            if ($client_id === 0) {
                // No client - simple format P{product}R{random}
                $asset_tag = sprintf(
                    'P%sR%s',
                    str_pad(strtoupper(base_convert($product_id, 10, 36)), 3, '0', STR_PAD_LEFT),
                    str_pad(strtoupper(base_convert(random_int(0, 46655), 10, 36)), 4, '0', STR_PAD_LEFT)
                );
            } else {
                // Has client - format with client and location
                $asset_tag = sprintf(
                    'P%sC%sL%sR%s',
                    str_pad(strtoupper(base_convert($product_id, 10, 36)), 3, '0', STR_PAD_LEFT),
                    str_pad(strtoupper(base_convert($client_id, 10, 36)), 3, '0', STR_PAD_LEFT),
                    str_pad(strtoupper(base_convert($location_id, 10, 36)), 2, '0', STR_PAD_LEFT),
                    str_pad(strtoupper(base_convert(random_int(0, 1295), 10, 36)), 2, '0', STR_PAD_LEFT)
                );
            }
        }

        // Check if this tag already exists
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM inventory WHERE inventory_asset_tag = ? AND inventory_id != ?");
        $stmt->execute([$asset_tag, $inventory_id]);
        if ($stmt->fetchColumn() > 0) {
            return $this->generateAssetTag($inventory_id, $client_id, $product_id, $location_id, $serial_number, $attempts + 1);
        }

        // Save the asset tag to the database
        $stmt = $this->pdo->prepare("UPDATE inventory SET inventory_asset_tag = ? WHERE inventory_id = ?");
        $stmt->execute([$asset_tag, $inventory_id]);
        return $asset_tag;
    }
}