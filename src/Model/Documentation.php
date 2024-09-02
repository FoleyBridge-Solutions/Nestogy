<?php
// src/Model/Documentation.php

namespace Twetech\Nestogy\Model;

use PDO;
use Twetech\Nestogy\Model\Support;
use Twetech\Nestogy\Model\Contact;
use Twetech\Nestogy\Model\Location;

class Documentation {
    private $pdo;
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAssets($client_id = false) {
        $sql = "SELECT * FROM assets";
        if ($client_id) {
            $sql .= " WHERE asset_client_id = :client_id";
        }
        $stmt = $this->pdo->prepare($sql);
        if ($client_id) {
            $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLicenses($client_id = false) {
        $sql = "SELECT * FROM software";
        if ($client_id) {
            $sql .= " WHERE software_client_id = :client_id";
        }
        $stmt = $this->pdo->prepare($sql);
        if ($client_id) {
            $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLogins($client_id = false) { // This method will return encrypted (not readable) passwords
        $sql = "SELECT * FROM logins";
        if ($client_id) {
            $sql .= " WHERE login_client_id = :client_id";
        }
        $stmt = $this->pdo->prepare($sql);
        if ($client_id) {
            $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getLogin($login_id) {
        $sql = "SELECT * FROM logins WHERE login_id = :login_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':login_id', $login_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getNetworks($client_id = false) {
        $sql = "SELECT * FROM networks";
        if ($client_id) {
            $sql .= " WHERE network_client_id = :client_id";
        }
        $stmt = $this->pdo->prepare($sql);
        if ($client_id) {
            $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getServices($client_id = false) {
        $sql = "SELECT * FROM services";
        if ($client_id) {
            $sql .= " WHERE service_client_id = :client_id";
        }
        $stmt = $this->pdo->prepare($sql);
        if ($client_id) {
            $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getVendors($client_id = false) {
        $sql = "SELECT * FROM vendors";
        if ($client_id) {
            $sql .= " WHERE vendor_client_id = :client_id";
        }
        $stmt = $this->pdo->prepare($sql);
        if ($client_id) {
            $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getExpirations($client_id = false) {
        $sql = "SELECT * FROM domains WHERE domain_expire < NOW() + INTERVAL 3 MONTH";
        if ($client_id) {
            $sql .= " AND domain_client_id = :client_id";
        }
        $stmt = $this->pdo->prepare($sql);
        if ($client_id) {
            $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $domain_expirations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT * FROM software WHERE software_expire < NOW() + INTERVAL 3 MONTH";
        if ($client_id) {
            $sql .= " AND software_client_id = :client_id";
        }
        $stmt = $this->pdo->prepare($sql);
        if ($client_id) {
            $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $software_expirations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT * FROM assets WHERE asset_warranty_expire < NOW() + INTERVAL 3 MONTH";
        if ($client_id) {
            $sql .= " AND asset_client_id = :client_id";
        }
        $stmt = $this->pdo->prepare($sql);
        if ($client_id) {
            $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $asset_expirations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'domains' => $domain_expirations,
            'software' => $software_expirations,
            'assets' => $asset_expirations
        ];
    }
    public function decryptLoginPassword($encrypted_password) {
        // Split the login into IV and Ciphertext
        $login_iv =  substr($encrypted_password, 0, 16);
        $login_ciphertext = substr($encrypted_password, 16);

        error_log("++++++++++\nDecrypting password: $encrypted_password");
        error_log("login_iv: $login_iv\n");
        error_log("login_ciphertext: $login_ciphertext\n");

        // Get the user session info.
        $user_encryption_session_ciphertext = $_SESSION['user_encryption_session_ciphertext'] ?? null;
        $user_encryption_session_iv =  $_SESSION['user_encryption_session_iv'] ?? null;
        $user_encryption_session_key = $_COOKIE['user_encryption_session_key'] ?? null;

        if (!$user_encryption_session_ciphertext || !$user_encryption_session_iv || !$user_encryption_session_key) {
            error_log("Missing session or cookie data for decryption.");
            return null;
        }

        error_log("user_encryption_session_ciphertext: $user_encryption_session_ciphertext\n");
        error_log("user_encryption_session_iv: $user_encryption_session_iv\n");
        error_log("user_encryption_session_key: $user_encryption_session_key\n");

        // Decrypt the session key to get the master key
        $site_encryption_master_key = openssl_decrypt(
            $user_encryption_session_ciphertext, 
            'aes-128-cbc', 
            $user_encryption_session_key, 
            0, 
            $user_encryption_session_iv
        );

        if ($site_encryption_master_key === false) {
            error_log("Failed to decrypt the site encryption master key.");
            return null;
        }

        error_log("site_encryption_master_key: $site_encryption_master_key\n");

        // Decrypt the login password using the master key
        $decrypted_password = openssl_decrypt(
            $login_ciphertext, 
            'aes-128-cbc', 
            $site_encryption_master_key, 
            0, 
            $login_iv
        );

        if ($decrypted_password === false) {
            error_log("Failed to decrypt the login password.");
            return null;
        }

        return $decrypted_password;
    }
}