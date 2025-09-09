<?php

use App\Domains\Ticket\Services\TimeTrackingService;
use App\Domains\Ticket\Models\Ticket;
use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Testing Time Tracking Service...\n\n";
    
    // Get service
    $service = app(TimeTrackingService::class);
    echo "✓ TimeTrackingService loaded\n";
    
    // Test rate methods
    $rateType = $service->determineRateType(\Carbon\Carbon::now());
    echo "✓ Current rate type: $rateType\n";
    
    $multiplier = $service->getRateMultiplier($rateType);
    echo "✓ Rate multiplier: $multiplier\n";
    
    $description = $service->getRateDescription($rateType);
    echo "✓ Rate description: $description\n";
    
    $visual = $service->getRateVisualIndicator($rateType);
    echo "✓ Visual indicator: " . json_encode($visual) . "\n";
    
    $currentInfo = $service->getCurrentRateInfo();
    echo "✓ Current rate info: " . json_encode($currentInfo) . "\n";
    
    // Test with a sample ticket if available
    $ticket = Ticket::first();
    $user = User::first();
    
    if ($ticket && $user) {
        echo "\n✓ Testing with Ticket #{$ticket->id} and User #{$user->id}\n";
        
        $smartInfo = $service->getSmartRateInfo(null, ['priority' => $ticket->priority]);
        echo "✓ Smart rate info: " . json_encode($smartInfo) . "\n";
        
        $dashboard = $service->getBillingDashboard($user);
        echo "✓ Billing dashboard: Today's hours: " . ($dashboard['today']['total_hours'] ?? 0) . "\n";
    } else {
        echo "\n⚠ No tickets or users in database - skipping advanced tests\n";
    }
    
    echo "\n✅ All tests passed!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}