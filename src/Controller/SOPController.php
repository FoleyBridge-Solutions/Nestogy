<?php
// src/Controller/SOPController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\Model\Support;
use Twetech\Nestogy\Model\Documentation;
use NumberFormatter;

/**
 * Controller for handling Standard Operating Procedures (SOP)
 */
class SOPController {
    /** @var \Twetech\Nestogy\View\View */
    private $view;
    
    /** @var \PDO */
    private $pdo;
    
    /** @var \Twetech\Nestogy\Auth\Auth */
    private $auth;
    
    /** @var \Twetech\Nestogy\Model\Accounting */
    private $accounting;
    
    /** @var \Twetech\Nestogy\Model\Documentation */
    private $documentation;
    
    /** @var array */
    private $data;
    
    /** @var \Twetech\Nestogy\Model\Support */
    private $support;
    
    /** @var \NumberFormatter */
    private $currency_format;

    /**
     * Initialize the SOP Controller
     *
     * @param \PDO $pdo Database connection instance
     */
    public function __construct($pdo) {
        $this->view = new View();
        $this->auth = new Auth($pdo);
        $this->accounting = new Accounting($pdo);
        $this->support = new Support($pdo);
        $this->documentation = new Documentation($pdo);
        $this->currency_format = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        if (!$this->auth->check()) {
            // Redirect to login page or handle unauthorized access
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Display SOP listing or render specific SOP
     *
     * @param int|null    $id      The SOP ID to display (optional)
     * @param string|null $version The specific version to display (optional)
     * @return void
     */
    public function index($id = null, $version = null) {
        if ($id) {
            $this->renderSOP($id, $version);
        } else {
            $this->data['action'] = [
                'title' => 'Create SOP',
                'modal' => 'sop_add_modal.php'
            ];
            $this->data['card']['title'] = 'Standard Operating Procedures';
            $this->data['table']['header_rows'] = ['Title', 'Description','Version', 'Actions'];
            $SOPs = $this->documentation->getSOPs();
            foreach ($SOPs as $SOP) {
                $this->data['table']['body_rows'][] = [
                    'title' => $SOP['title'],
                    'description' => $SOP['description'],
                    'version' => $SOP['version'],
                    'actions' => '
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <a href="?page=sop&id='.$SOP['id'].'">View</a>
                            </li>
                            <li class="list-inline-item">
                                <a href="?page=sop&id='.$SOP['id'].'">Edit</a>
                            </li>
                            <li class="list-inline-item">
                                <a href="?page=sop&id='.$SOP['id'].'">Delete</a>
                            </li>
                        </ul>
                    '
                ];
            }
        }
        $this->view->render('simpleTable', $this->data);
    }

    /**
     * Render a specific SOP
     *
     * @param int         $id      The SOP ID to render
     * @param string|null $version The specific version to render (optional)
     * @return void
     */
    public function renderSOP($id, $version = null) {
        $SOP = $this->documentation->getSOP($id, $version);
        $this->data['sop'] = $SOP;
        $this->view->render('sop', $this->data);
    }
}
