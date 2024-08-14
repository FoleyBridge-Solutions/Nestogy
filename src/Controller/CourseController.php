<?php
// src/Controller/CourseController.php

namespace Twetech\Nestogy\Controller;

use Twetech\Nestogy\View\View;
use Twetech\Nestogy\Auth\Auth;
use Twetech\Nestogy\Model\Accounting;
use Twetech\Nestogy\Model\Client;

class CourseController {
    private $view;
    
    public function __construct() {
        $this->view = new View();
    }

    public function index() {
        $data = [];
        $this->view->render('course', $data);
    }
}