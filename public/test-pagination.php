<?php
// Simple test to see if PHP is crashing
echo "PHP is working\n";
echo "Page parameter: " . ($_GET['page'] ?? 'none') . "\n";

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $contact = App\Domains\Client\Models\Contact::first();
    if ($contact && $contact->client) {
        $tickets = $contact->client->tickets()->paginate(10);
        echo "Tickets found: " . $tickets->total() . "\n";
        echo "Current page: " . $tickets->currentPage() . "\n";
        if ($tickets->hasPages()) {
            echo "Rendering pagination links...\n";
            echo $tickets->links();
            echo "\nPagination rendered successfully\n";
        }
    }
    echo "Test completed successfully\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
