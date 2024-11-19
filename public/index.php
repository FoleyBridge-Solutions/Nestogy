<?php

// public/index.php
require '../bootstrap.php';

use Twetech\Nestogy\Core\Router;

//Get the domain
$domain = $_SERVER['HTTP_HOST'];

$router = new Router($domain);
$router->dispatch();
