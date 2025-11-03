<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Broadcast;

class TestDirectBroadcast extends Command
{
    protected $signature = 'test:direct-broadcast {channel} {event} {--data=}';
    protected $description = 'Test direct broadcast to Reverb';

    public function handle()
    {
        $channel = $this->argument('channel');
        $event = $this->argument('event');
        $data = json_decode($this->option('data') ?: '{}', true);

        $this->info("Broadcasting to channel: {$channel}");
        $this->info("Event: {$event}");
        $this->info("Data: " . json_encode($data, JSON_PRETTY_PRINT));
        
        try {
            $broadcaster = Broadcast::connection('reverb');
            
            $this->info("\nBroadcast driver: " . config('broadcasting.default'));
            $this->info("Reverb config:");
            $this->info("  Host: " . config('broadcasting.connections.reverb.options.host'));
            $this->info("  Port: " . config('broadcasting.connections.reverb.options.port'));
            $this->info("  App ID: " . config('broadcasting.connections.reverb.app_id'));
            $this->info("  Key: " . config('broadcasting.connections.reverb.key'));
            
            $this->newLine();
            $this->info("Sending broadcast...");
            
            $broadcaster->broadcast([$channel], $event, $data);
            
            $this->info("✓ Broadcast sent successfully!");
            
        } catch (\Exception $e) {
            $this->error("✗ Broadcast failed: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
