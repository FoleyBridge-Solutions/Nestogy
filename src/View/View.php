<?php
// src/View/View.php

namespace Twetech\Nestogy\View;
use NumberFormatter;

/**
 * View Class
 * 
 * Handles the rendering of templates and view components
 */
class View {

    /**
     * Renders a template with the provided data
     *
     * @param string $template    The name of the template to render
     * @param array  $data       Optional data to be extracted into template scope
     * @param bool   $client_page Whether to include the client navbar
     * @return void
     */
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

    /**
     * Renders an error page with the given message
     *
     * @param array $message Array containing 'title' and 'message' keys
     * @return void
     */
    public function error($message) {
        extract($message);
        require "../src/View/error.php";
    }
}
