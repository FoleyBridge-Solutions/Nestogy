<?php
// src/Controller/ReportsController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\Accounting;
class ReportsController{
    private $pdo;
    private $view;
    private $auth;
    private $accounting;


    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->view = new View();
        $this->auth = new Auth($pdo);
        $this->accounting = new Accounting($pdo);

        if (!$this->auth->check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
    }

    public function index($report){
        switch($report){
            case 'tax_summary':
                $this->taxReport();
                break;
            case 'collections':
                $this->collectionsReport();
                break;
            default:
                header('Location: /');
                exit;
        }
    }

    private function taxReport(){
        $tax_report = $this->accounting->getTaxReport();
        $data = [
            'tax_report' => $tax_report
        ];
        $this->view->render('reports/tax', $data);
    }

    private function collectionsReport(){
        $collections_report = $this->accounting->getCollectionsReport();
        $this->view->render('reports/collections', $collections_report);
    }

}