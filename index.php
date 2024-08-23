<?php 
// public/login.php
require 'bootstrap.php';

use Twetech\Nestogy\Auth\Auth;

$auth = new Auth($pdo);

// Check if the user has a valid "Remember Me" cookie
$auth->checkRememberMe();

if (Auth::check()) {
    // User is already logged in, redirect them to the dashboard
    header('Location: /public/');
    exit;
} else {
    header('Location: /portal/login.php');
    exit;
}

?>