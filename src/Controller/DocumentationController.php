<?php
// src/Controller/DocumentationController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\Model\Client;
use Twetech\Nestogy\Model\Documentation;
use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;

class DocumentationController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;

        if (!Auth::check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
    }

    public function index() {
        //Redirect to /public/?page=home temporarily
        // TODO: Implement the documentation home page
        header('Location: /public/?page=home');
        exit;
    }

    private function clientAccessed($client_id) {
        $clientModel = new Client($this->pdo);
        $clientModel->clientAccessed($client_id);
    }

    public function show($documentation_type, $client_id = false) {
        $view = new View();
        $auth = new Auth($this->pdo);
        // Check if user has access to the documentation class
        if (!$auth->checkClassAccess($_SESSION['user_id'], 'documentation', 'view')) {
            // If user does not have access, display an error message
            $view->error([
                'title' => 'Access Denied',
                'message' => 'You do not have permission to view documentation.'
            ]);
            return;
        }
        $documentationModel = new Documentation($this->pdo);
        $client_page = false;
        $data = [];

        if ($client_id) {
            $this->clientAccessed($client_id);
            // Check if user has access to the client
            if (!$auth->checkClientAccess($_SESSION['user_id'], $client_id, 'view')) {
                // If user does not have access, display an error message
                $view->error([
                    'title' => 'Access Denied',
                    'message' => 'You do not have permission to view this client.'
                ]);
                return;
            }
            $client_page = true;
            $client = new Client($this->pdo);
            $client_header = $client->getClientHeader($client_id);
            $data['client_header'] = $client_header['client_header'];
        }
        switch ($documentation_type) {
            case 'asset': {
                $assets = $documentationModel->getAssets($client_id ? $client_id : false);
                $data['card']['title'] = 'Assets';
                $data['table']['header_rows'] = [
                    'Name',
                    'Type',
                    'Model',
                    'Serial',
                    'OS',
                    'IP',
                    'Install Date',
                    'Assigned To',
                    'Location',
                    'Status',
                ];
                    
                $data['table']['body_rows'] = [];

                foreach ($assets as $asset) {
                    $data['table']['body_rows'][] = [
                        $asset['asset_name'],
                        $asset['asset_type'],
                        $asset['asset_model'],
                        $asset['asset_serial'],
                        $asset['asset_os'],
                        $asset['asset_ip'],
                        $asset['asset_install_date'],
                        0, // todo get assigned to
                        $asset['asset_location_id'],
                        $asset['asset_status'],
                    ];
                }

                break;
            }
            case 'license': {
                $licenses = $documentationModel->getLicenses($client_id);
                $data['card']['title'] = 'Licenses';
                $data['table']['header_rows'] = [
                    'Software',
                    'Type',
                    'License Type',
                    'Seats'
                ];
                $data['table']['body_rows'] = [];

                foreach ($licenses as $license) {
                    $data['table']['body_rows'][] = [
                        $license['software_name'],
                        $license['software_type'],
                        $license['software_license_type'],
                        $license['software_seats']
                    ];
                }
                break;
            }
            case 'login': {
                $logins = $documentationModel->getLogins($client_id);
                $data['card']['title'] = 'Logins';
                $data['table']['header_rows'] = [
                    'Name',
                    'Username',
                    'Password',
                    'OTP',
                    'URL'
                ];
                $data['table']['body_rows'] = [];

                foreach ($logins as $login) {
                    $data['table']['body_rows'][] = [
                        $login['login_name'],
                        $this->decryptLoginPassword($login['login_username']),
                        $this->decryptLoginPassword($login['login_password']),
                        $login['login_otp_secret'],
                        '<a href="'.$login['login_uri'].'" target="_blank">'.$login['login_uri'].'</a>'
                    ];
                }
                break;
            }
            case 'network': {
                $networks = $documentationModel->getNetworks($client_id);
                $data['card']['title'] = 'Networks';
                $data['table']['header_rows'] = [
                    'Name',
                    'VLAN',
                    'Subnet',
                    'Gateway',
                    'DCHP Pool',
                    'Location'
                ];
                $data['table']['body_rows'] = [];

                foreach ($networks as $network) {
                    $data['table']['body_rows'][] = [
                        $network['network_name'],
                        $network['network_vlan'],
                        $network['network'],
                        $network['network_gateway'],
                        $network['network_dhcp_range'],
                        $network['network_location_id']
                    ];
                }
                break;
            }
            case 'service': {
                $services = $documentationModel->getServices($client_id);
                $data['card']['title'] = 'Services';
                $data['table']['header_rows'] = [
                    'Name',
                    'Category',
                    'Importance',
                    'Updated'
                ];
                $data['table']['body_rows'] = [];

                foreach ($services as $service) {
                    $data['table']['body_rows'][] = [
                        $service['service_name'],
                        $service['service_category'],
                        $service['service_importance'],
                        $service['service_updated_at']
                    ];
                }
                break;
            }
            case 'vendor': {
                $vendors = $documentationModel->getVendors($client_id);
                $data['card']['title'] = 'Vendors';
                $data['table']['header_rows'] = [
                    'Name',
                    'Contact',
                    'SLA',
                    'Notes'
                ];
                $data['table']['body_rows'] = [];

                foreach ($vendors as $vendor) {
                    $data['table']['body_rows'][] = [
                        $vendor['vendor_name'],
                        $vendor['vendor_contact_name'].' <a href="mailto:'.$vendor['vendor_email'].'">'.$vendor['vendor_email'].'</a> <a href="tel:'.$vendor['vendor_phone'].'">'.$vendor['vendor_phone'].'</a>',
                        $vendor['vendor_sla'],
                        $vendor['vendor_notes']
                    ];
                }

                break;
            }
            case 'file': {
                $message = [
                    'title' => 'Page not found',
                    'message' => 'File documentation not implemented yet.'
                ];
                $view->error($message);
                exit;
            }
            case 'document': {
                $message = [
                    'title' => 'Page not found',
                    'message' => 'Document documentation not implemented yet.'
                ];
                $view->error($message);
                exit;
            }

            default: {
                $view->error([
                    'title' => 'Page not found',
                    'message' => 'Documentation type not implemented yet.'
                ]);
                exit;
            }
        }
        $view->render('simpleTable', $data, $client_page);
    }

    private function decryptLoginPassword($encrypted_password) {
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