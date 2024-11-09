<?php

// public/index.php
require '../bootstrap.php';

use Twetech\Nestogy\Core\Router;

// // Benchmarking
// $start_time = microtime(true);

//Get the domain
$domain = $_SERVER['HTTP_HOST'];

$router = new Router($domain);
$router->dispatch();

// // Benchmarking
// $end_time = microtime(true);
// $execution_time = $end_time - $start_time;
// if ($execution_time > 0.5) { // Save the execution time to a file
// file_put_contents('execution_time.txt', "\n". $_SERVER['REQUEST_URI'] ." @ ". date('Y-m-d H:i:s') ." - " . $execution_time . " seconds", FILE_APPEND);
// }