<?php
// src/Controller/DocumentationController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\Model\Client;
use Twetech\Nestogy\Model\Documentation;
use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;

/**
 * Controller handling documentation-related operations
 */
class DocumentationController {
    /** @var \PDO Database connection */
    private $pdo;

    /**
     * Constructor for DocumentationController
     *
     * @param \PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;

        if (!Auth::check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Display the documentation index page
     *
     * @return void
     */
    public function index() {
        //Redirect to /public/?page=home temporarily
        // TODO: Implement the documentation home page
        header('Location: /public/?page=home');
        exit;
    }

    /**
     * Update client's last accessed timestamp
     *
     * @param int $client_id Client identifier
     * @return void
     */
    private function clientAccessed($client_id) {
        $clientModel = new Client($this->pdo);
        $clientModel->clientAccessed($client_id);
    }

    /**
     * Display specific documentation type for a client
     *
     * @param string $documentation_type Type of documentation to display (asset, license, login, network, service, vendor, file, document)
     * @param int|false $client_id Optional client identifier
     * @return void
     */
    public function show($documentation_type, $client_id = false) {
        $view = new View();
        $auth = new Auth($this->pdo);
        $documentationModel = new Documentation($this->pdo);
        $client_page = false;
        $data = [];

        if ($client_id) {
            $this->clientAccessed($client_id);
            $client_page = true;
            $client = new Client($this->pdo);
            $client_header = $client->getClientHeader($client_id);
            $data['client_header'] = $client_header['client_header'];
            $data['return_page'] = [
                'name' => 'Clients',
                'link' => 'clients'
            ];
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
                $data['action'] = [
                    'title' => 'Add Asset',
                    'modal' => 'client_asset_add_modal.php?client_id='.$client_id
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
                $data['action'] = [
                    'title' => 'Add License',
                    'modal' => 'client_license_add_modal.php?client_id='.$client_id
                ];
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
                    'URL',
                    'Actions'
                ];
                $data['action'] = [
                    'title' => 'Add Login',
                    'modal' => 'client_login_add_modal.php?client_id='.$client_id
                ];
                $data['table']['body_rows'] = [];
                foreach ($logins as $login) {
                    $data['table']['body_rows'][] = [
                        $login['login_name'],
                        $this->decryptLoginPassword($login['login_username']),
                        $this->decryptLoginPassword($login['login_password']),
                        $login['login_otp_secret'],
                        '<a href="'.$login['login_uri'].'" target="_blank">'.$login['login_uri'].'</a>',
                        '<a class="btn btn-primary loadModalContentBtn" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="client_login_edit_modal.php?login_id='.$login['login_id'].'">Edit</button>'
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
                $data['action'] = [
                    'title' => 'Add Network',
                    'modal' => 'client_network_add_modal.php?client_id='.$client_id
                ];
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
                $data['action'] = [
                    'title' => 'Add Service',
                    'modal' => 'client_service_add_modal.php?client_id='.$client_id
                ];
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
                    'Notes',
                    'Actions'
                ];
                $data['action'] = [
                    [
                        'title' => 'Add Vendor',
                        'modal' => 'vendor_add_modal.php?client_id='.$client_id
                    ],
                    [
                        'title' => 'Add Vendor from Template',
                        'modal' => 'vendor_add_from_template_modal.php?client_id='.$client_id
                    ]
                ];
                $data['table']['body_rows'] = [];
                foreach ($vendors as $vendor) {
                    $data['table']['body_rows'][] = [
                        $vendor['vendor_name'],
                        $vendor['vendor_contact_name'].' <a href="mailto:'.$vendor['vendor_email'].'">'.$vendor['vendor_email'].'</a> <a href="tel:'.$vendor['vendor_phone'].'">'.$vendor['vendor_phone'].'</a>',
                        $vendor['vendor_sla'],
                        $vendor['vendor_notes'],
                        '<a href="#" class="dropdown-item loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="client_vendor_edit_modal.php?vendor_id=' . $vendor['vendor_id'] . '">
                            <i class="fa fa-pencil"></i>
                        </a>'
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

    /**
     * Decrypt login password using Documentation model
     *
     * @param string $encrypted_password Encrypted password string
     * @return string Decrypted password
     */
    private function decryptLoginPassword($encrypted_password) {
        $documentationModel = new Documentation($this->pdo);
        return $documentationModel->decryptLoginPassword($encrypted_password);
    }
}