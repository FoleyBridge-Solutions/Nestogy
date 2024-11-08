<?php
// src/Controller/AdministrationController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\Administration;

/**
 * Administration Controller
 * 
 * Handles all administrative functions including user management,
 * mail queue, API keys, and various system settings
 */
class AdministrationController {
    private $pdo;
    private $auth;
    private $administration;
    private $view;

    /**
     * Constructor
     *
     * @param \PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->auth = new Auth($this->pdo);
        $this->view = new View();
        $this->administration = new Administration($this->pdo);
        if (!$this->auth->check()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Displays user management interface
     *
     * @return void
     */
    private function showUsers() {
        $users = $this->auth->getUsers();

        $data['card']['title'] = 'Users';
        $data['table']['header_rows'] = ['Name','Status', 'Email', 'Role', 'Actions'];
        $data['action'] = [
            'title' => 'Add User',
            'modal' => 'admin_user_add_modal.php'
        ];
        foreach ($users as $user) {

            $user_role = $user['user_role'];
            switch ($user_role) {
                case 'admin':
                    $user_role = 'Administrator';
                    break;
                case 'tech':
                    $user_role = 'Technician';
                    break;
                case 'accountant':
                    $user_role = 'Accountant';
                    break;
                default:
                    $user_role = 'User';
                    break;
            }

            $status = $user['user_status'] == 1 ? 'Active' : 'Inactive';

            $data['table']['body_rows'][] = [
                $user['user_name'],
                $status,
                $user['user_email'],
                $user_role,
                "<ul>
                    <li><a href='#' class='loadModalContentBtn' data-bs-toggle='modal' data-bs-target='#dynamicModal' data-modal-file='admin_user_edit_modal.php?user_id=" . $user['user_id'] . "'>Edit</a></li>

                </ul>"
            ];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * Displays mail queue or sent emails
     *
     * @param bool $sent Whether to show sent emails instead of queue
     * @return void
     */
    private function showMailQueue($sent = false) {
        $mailQueue = $this->auth->getMailQueue($sent);
        $data['table']['header_rows'] = ['Date Queued', 'Email', 'Subject', 'Message', 'Status', 'Actions'];
        if (!$sent) {
            $data['action'] = [
                'title' => 'View Sent Emails',
                'url' => '/public/?page=admin&admin_page=mail_queue&sent=true'
            ];
            $data['card']['title'] = 'Mail Queue';
        } else {
            $data['action'] = [
                'title' => 'View Mail Queue',
                'url' => '/public/?page=admin&admin_page=mail_queue'
            ];
            $data['card']['title'] = 'Sent Emails';
        }
        $statuses = ['Queued', 'Sending', 'Failed to Send', 'Sent'];
        foreach ($mailQueue as $mail) {
            $data['table']['body_rows'][] = [
                date('F j, Y, g:i a', strtotime($mail['email_queued_at'])),
                $mail['email_recipient'],
                $mail['email_subject'],
                '<a href="#" class="loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="admin_email_preview_modal.php?email_id=' . $mail['email_id'] . '">Preview</a>',
                $statuses[$mail['email_status']],
                "<ul>
                    <li><a href='/admin/mail_queue/" . $mail['email_id'] . "/delete'>Delete</a></li>
                </ul>"
            ];
        }
        if (count($mailQueue) == 0) {
            $data['table']['header_rows'] = ['No emails in queue'];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * Displays API keys management interface
     *
     * @return void
     */
    private function showAPIKeys() {
        $apiKeys = $this->administration->getAPIKeys();
        $data['table']['header_rows'] = ['API Key', 'Created At', 'Expires At', 'Client ID', 'Actions'];
        $data['action'] = [
            'title' => 'Add API Key',
            'modal' => 'admin_api_key_add_modal.php'
        ];
        foreach ($apiKeys as $apiKey) {
            $data['table']['body_rows'][] = [
                $apiKey['api_key_name'],
                $apiKey['api_key_created_at'],
                $apiKey['api_key_expire'],
                $apiKey['api_key_client_id'],
                "<ul>
                    <li><a href='/admin/api_keys/" . $apiKey['api_key_id'] . "/delete'>Delete</a></li>
                </ul>"
            ];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * Displays tag management interface
     *
     * @return void
     */
    private function showTags() {
        $tags = $this->administration->getTags();
        $data['table']['header_rows'] = ['Tag', 'Description', 'Actions'];
        foreach ($tags as $tag) {
            $data['table']['body_rows'][] = [
                $tag['tag_name'],
                $tag['tag_description'],
                "<ul>
                    <li><a href='/admin/tags/" . $tag['tag_id'] . "/delete'>Delete</a></li>
                </ul>"
            ];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * Displays category management interface
     *
     * @return void
     */
    private function showCategories() {
        $categories = $this->administration->getCategories();
        $data['table']['header_rows'] = ['Category', 'Description', 'Actions'];
        foreach ($categories as $category) {
            $data['table']['body_rows'][] = [
                $category['category_name'],
                $category['category_description'],
                "<ul>
                    <li><a href='/admin/categories/" . $category['category_id'] . "/delete'>Delete</a></li>
                </ul>"
            ];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * Displays tax rates management interface
     *
     * @return void
     */
    private function showTaxes() {
        $taxes = $this->administration->getTaxes();
        $data['table']['header_rows'] = ['Tax', 'Rate', 'Actions'];
        $data['action'] = [
            'title' => 'Add Tax',
            'modal' => 'admin_tax_add_modal.php'
        ];
        foreach ($taxes as $tax) {
            $data['table']['body_rows'][] = [
                $tax['tax_name'],
                $tax['tax_percent'] . '%',
                "<ul>
                    <li><a href='/admin/taxes/" . $tax['tax_id'] . "/delete'>Delete</a></li>
                </ul>"
            ];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * Displays account types management interface
     *
     * @return void
     */
    private function showAccountTypes() {
        $accountTypes = $this->administration->getAccountTypes();
        $data['table']['header_rows'] = ['Account Type', 'Description', 'Actions'];
        foreach ($accountTypes as $accountType) {
            $data['table']['body_rows'][] = [
                $accountType['account_type_name'],
                $accountType['account_type_description'],
                "<ul>
                    <li><a href='/admin/account_types/" . $accountType['account_type_id'] . "/delete'>Delete</a></li>
                </ul>"
            ];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * Displays vendor templates management interface
     *
     * @return void
     */
    private function showVendorTemplates() {
        $vendorTemplates = $this->administration->getVendorTemplates();
        $data['table']['header_rows'] = ['Vendor Template', 'Description', 'Actions'];
        foreach ($vendorTemplates as $vendorTemplate) {
            $data['table']['body_rows'][] = [
                $vendorTemplate['vendor_template_name'],
                $vendorTemplate['vendor_template_description'],
                "<ul>
                    <li><a href='/admin/vendor_templates/" . $vendorTemplate['vendor_template_id'] . "/delete'>Delete</a></li>
                </ul>"
            ];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * Displays license templates management interface
     *
     * @return void
     */
    private function showLicenseTemplates() {
        $licenseTemplates = $this->administration->getLicenseTemplates();
        $data['table']['header_rows'] = ['License Template', 'Description', 'Actions'];
        foreach ($licenseTemplates as $licenseTemplate) {
            $data['table']['body_rows'][] = [
                $licenseTemplate['license_template_name'],
                $licenseTemplate['license_template_description'],
                "<ul>
                    <li><a href='/admin/license_templates/" . $licenseTemplate['license_template_id'] . "/delete'>Delete</a></li>
                </ul>"
            ];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * Displays document templates management interface
     *
     * @return void
     */
    private function showDocumentTemplates() {
        $documentTemplates = $this->administration->getDocumentTemplates();
        $data['table']['header_rows'] = ['Document Template', 'Description', 'Actions'];
        foreach ($documentTemplates as $documentTemplate) {
            $data['table']['body_rows'][] = [
                $documentTemplate['document_template_name'],
                $documentTemplate['document_template_description'],
                "<ul>
                    <li><a href='/admin/document_templates/" . $documentTemplate['document_template_id'] . "/delete'>Delete</a></li>
                </ul>"
            ];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * Displays audit logs interface
     *
     * @return void
     */
    private function showAuditLogs() {
        $auditLogs = $this->administration->getAuditLogs();
        $data['table']['header_rows'] = ['Audit Log', 'Description', 'Actions'];
        foreach ($auditLogs as $auditLog) {
            $data['table']['body_rows'][] = [
                $auditLog['audit_log_name'],
                $auditLog['audit_log_description'],
                "<ul>
                    <li><a href='/admin/audit_logs/" . $auditLog['audit_log_id'] . "/delete'>Delete</a></li>
                </ul>"
            ];
        }
        $this->view->render('simpleTable', $data);
    }

    /**
     * Displays backup management interface
     *
     * @return void
     */
    private function showBackup() {
        $data['card']['title'] = 'Backup';
        $data['table']['header_rows'] = ['Backup', 'Description', 'Actions'];
        $data['table']['body_rows'][] = [
            'Backup',
            'Description',
            "<ul>
                <li><a href='/admin/backup/create'>Create Backup</a></li>
            </ul>"
        ];
        $this->view->render('simpleTable', $data);
    }

    /**
     * Main routing method for administration pages
     *
     * @param string $page The administrative page to display
     * @param bool $sent Optional parameter for mail queue display
     * @return void
     */
    public function index($page, $sent = false) {
        switch ($page) {
            case 'users':
                $this->showUsers();
                break;
            case 'mail_queue':
                $this->showMailQueue($sent);
                break;
            case 'api_keys':
                $this->showAPIKeys();
                break;
            case 'tags':
                $this->showTags();
                break;
            case 'categories':
                $this->showCategories();
                break;
            case 'taxes':
                $this->showTaxes();
                break;
            case 'account_types':
                $this->showAccountTypes();
                break;
            case 'vendor_templates':
                $this->showVendorTemplates();
                break;
            case 'license_templates':
                $this->showLicenseTemplates();
                break;
            case 'document_templates':
                $this->showDocumentTemplates();
                break;
            case 'audit_logs':
                $this->showAuditLogs();
                break;
            case 'backup':
                $this->showBackup();
                break;
            case 'debug':
                header('Location: /public/?page=debug');
                exit; 
            default:
                header('Location: /public/?page=dashboard');
                exit;
        }
    }
}