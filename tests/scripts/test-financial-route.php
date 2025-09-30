<?php

// Test script to check if financial/invoices route is accessible

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a test request
$request = Illuminate\Http\Request::create('/financial/invoices', 'GET');

// Set up a test user session (you'll need to adjust the user ID)
$app->make('auth')->loginUsingId(1);

// Handle the request
$response = $kernel->handle($request);

echo "Status Code: " . $response->getStatusCode() . PHP_EOL;
echo "Response Headers:\n";
foreach ($response->headers->all() as $name => $values) {
    foreach ($values as $value) {
        echo "$name: $value\n";
    }
}

if ($response->getStatusCode() === 302) {
    echo "\nRedirect Location: " . $response->headers->get('Location') . PHP_EOL;
}

$kernel->terminate($request, $response);