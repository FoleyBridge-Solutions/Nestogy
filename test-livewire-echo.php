<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Livewire Echo Integration\n";
echo "==================================\n\n";

// Check if Livewire is installed
echo "Livewire installed: " . (class_exists(\Livewire\Component::class) ? 'Yes' : 'No') . "\n";

// Check if Echo is configured in bootstrap.js
$bootstrapPath = __DIR__.'/resources/js/bootstrap.js';
if (file_exists($bootstrapPath)) {
    $content = file_get_contents($bootstrapPath);
    echo "Echo configured in bootstrap.js: " . (strpos($content, 'window.Echo') !== false ? 'Yes' : 'No') . "\n";
}

// Check broadcast config
echo "\nBroadcast Configuration:\n";
echo "  Driver: " . config('broadcasting.default') . "\n";
echo "  Reverb Host: " . config('broadcasting.connections.reverb.options.host') . "\n";
echo "  Reverb App ID: " . config('broadcasting.connections.reverb.app_id') . "\n";

// Test component
echo "\nTesting AssetRmmStatus Component:\n";
$asset = App\Domains\Asset\Models\Asset::find(52);
if ($asset) {
    $component = new App\Livewire\Assets\AssetRmmStatus();
    $component->mount($asset);
    $listeners = $component->getListeners();
    echo "  Asset ID: " . $asset->id . "\n";
    echo "  Listeners registered:\n";
    foreach ($listeners as $event => $method) {
        echo "    - $event => $method\n";
    }
} else {
    echo "  Asset 52 not found\n";
}

echo "\nDone!\n";
