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

    public function index($page) {
        switch ($page) {
            case 'users':
                $this->showUsers();
                break;
        }
    }
}