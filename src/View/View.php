<?php
// src/View/View.php

namespace Twetech\Nestogy\View;
use NumberFormatter;

class View {
    public function render($template, $data = [], $client_page = false) {
        if ($template === 'error') {
            $this->error([
                'title' => 'Programatic Error',
                'message' => 'An error occurred' . $data['message']
            ]);
            return;
        }
        extract($_SESSION);
        extract($data);
        $currency_format = numfmt_create('en_US', NumberFormatter::CURRENCY);
        require "../src/View/header.php";
        require "../src/View/navbar.php";
        if ($client_page) {
            require "../src/View/client_navbar.php";
        }
                 require "../src/View/$template.php";
        require "../src/View/footer.php";
    }

    public function error($message) {
        extract($message);
        require "../src/View/error.php";
    }
}
