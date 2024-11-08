<?php
// src/Model/Documentation.php

namespace Twetech\Nestogy\Model;

use PDO;
use Twetech\Nestogy\Model\Support;
use Twetech\Nestogy\Model\Contact;
use Twetech\Nestogy\Model\Location;

/**
 * Documentation class handles retrieval and management of various documentation types
 * including assets, licenses, logins, networks, services, vendors, and SOPs.
 */
class Documentation {
    /** @var PDO Database connection instance */
    private $pdo;

    /**
     * Constructor for Documentation class
     *
     * @param PDO $pdo Database connection instance
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves all assets or assets for a specific client
     *
     * @param int|false $client_id Optional client ID to filter results
     * @return array Array of assets
     */
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

    /**
     * Retrieves all software licenses or licenses for a specific client
     *
     * @param int|false $client_id Optional client ID to filter results
     * @return array Array of software licenses
     */
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

    /**
     * Retrieves all encrypted logins or logins for a specific client
     *
     * @param int|false $client_id Optional client ID to filter results
     * @return array Array of encrypted login credentials
     */
    public function getLogins($client_id = false) {
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

    /**
     * Retrieves a specific login by ID
     *
     * @param int $login_id Login ID to retrieve
     * @return array|false Login data or false if not found
     */
    public function getLogin($login_id) {
        $sql = "SELECT * FROM logins WHERE login_id = :login_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':login_id', $login_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all networks or networks for a specific client
     *
     * @param int|false $client_id Optional client ID to filter results
     * @return array Array of networks
     */
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

    /**
     * Retrieves all services or services for a specific client
     *
     * @param int|false $client_id Optional client ID to filter results
     * @return array Array of services
     */
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

    /**
     * Retrieves all vendors or vendors for a specific client
     *
     * @param int|false $client_id Optional client ID to filter results
     * @return array Array of vendors
     */
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

    /**
     * Retrieves items expiring within the next 3 months
     *
     * @param int|false $client_id Optional client ID to filter results
     * @return array Associative array of expiring domains, software, and assets
     */
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

    /**
     * Decrypts an encrypted login password using session-based encryption
     *
     * @param string $encrypted_password The encrypted password to decrypt
     * @return string|null Decrypted password or null if decryption fails
     */
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

    /**
     * Retrieves all Standard Operating Procedures (SOPs)
     *
     * @return array Array of SOPs
     */
    public function getSOPs() {
        $sql = "SELECT * FROM sops";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves a specific SOP by ID and optional version
     *
     * @param int $id SOP ID to retrieve
     * @param string|null $version Optional specific version to retrieve
     * @return array SOP data including content
     */
    public function getSOP($id, $version = null) {
        $sql = "SELECT * FROM sops WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $sop = $stmt->fetch(PDO::FETCH_ASSOC);
        if (is_null($version)) {
            //find the latest version of the sop
            $versions = scandir("/var/www/portal.twe.tech/uploads/sops/{$sop['file_path']}");
            $latest_version = null;
            foreach ($versions as $version) {
                if (strpos($version, 'v') !== false) {
                    $latest_version = $version;
                }
            }
        } else {
            $latest_version = $version;
        }

        $sop['content'] = file_get_contents("/var/www/portal.twe.tech/uploads/sops/{$sop['file_path']}/{$latest_version}");
        return $sop;
    }

    /**
     * Creates a new Standard Operating Procedure (SOP)
     *
     * @param string $title SOP title
     * @param string $description SOP description
     * @param string $version Initial version number
     * @param string $file_path Path to SOP file
     * @return void
     */
    public function createSOP($title, $description, $version, $file_path) {
        $sql = "INSERT INTO sops (title, description, version, file_path) VALUES (:title, :description, :version, :file_path)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':version', $version, PDO::PARAM_STR);
        $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * Saves updated content for an existing SOP
     *
     * @param int $SOP_id ID of the SOP to update
     * @param string $content New content for the SOP
     * @return void
     */
    public function saveSOP($SOP_id, $content) {

    }
}