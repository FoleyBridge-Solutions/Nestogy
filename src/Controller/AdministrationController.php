<?php
// src/Controller/AdministrationController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;

class AdministrationController {
    private $pdo;
    private $auth;
    private $view;
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->auth = new Auth($this->pdo);
        $this->view = new View();
    }
    private function showUsers() {
        $users = $this->auth->getUsers();

        $data['card']['title'] = 'Users';
        $data['table']['header_rows'] = ['Name','Status', 'Email', 'Role', 'Actions'];
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
                    <li><a href='/admin/users/" . $user['user_id'] . "/edit'>Edit</a></li>
                    <li><a href='/admin/users/" . $user['user_id'] . "/delete'>Delete</a></li>
                    <li><a href='/admin/users/" . $user['user_id'] . "/reset'>Reset</a></li>
                </ul>"
            ];
        }
        $this->view->render('simpleTable', $data);
    }

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
    

    public function index($page, $sent = false) {
        switch ($page) {
            case 'users':
                $this->showUsers();
                break;
            case 'mail_queue':
                $this->showMailQueue($sent);
                break;
        }
    }
}