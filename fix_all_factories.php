<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$factories_to_fix = [
    'CollectionNote' => ['company_id', 'client_id', 'invoice_id', 'note', 'created_by', 'follow_up_date'],
    'ClientPortalSession' => ['company_id', 'client_portal_user_id', 'token', 'ip_address', 'user_agent', 'last_activity'],
    'ClientPortalUser' => ['company_id', 'client_id', 'email', 'password', 'is_active'],
    'CommunicationLog' => ['company_id', 'client_id', 'user_id', 'type', 'direction', 'subject', 'body', 'communication_date'],
];

foreach ($factories_to_fix as $model => $columns) {
    echo "Would fix $model factory to use columns: " . implode(', ', $columns) . "\n";
}
