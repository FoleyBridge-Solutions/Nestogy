<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix RMM online status for existing assets that don't have rmm_online field
        $assets = DB::table('assets')
            ->whereNotNull('notes')
            ->get();
            
        foreach ($assets as $asset) {
            $rmmData = json_decode($asset->notes, true);
            
            if ($rmmData && !isset($rmmData['rmm_online'])) {
                // Determine online status based on last_seen timestamp
                $isOnline = false;
                if (isset($rmmData['rmm_last_seen'])) {
                    try {
                        $lastSeen = \Carbon\Carbon::parse($rmmData['rmm_last_seen']);
                        // Consider online if last seen within 4 hours (more appropriate for business environment)
                        $isOnline = $lastSeen->diffInMinutes() < 240;
                    } catch (\Exception $e) {
                        $isOnline = false;
                    }
                }
                
                // Add rmm_online field to notes
                $rmmData['rmm_online'] = $isOnline;
                
                DB::table('assets')
                    ->where('id', $asset->id)
                    ->update([
                        'notes' => json_encode($rmmData),
                        'updated_at' => now()
                    ]);
            }
        }
        
        // Update server assets to use 'Deployed' status since they're infrastructure
        // Servers shouldn't be in the check-in/out system
        DB::table('assets')
            ->where('type', 'Server')
            ->where('status', 'Ready To Deploy')
            ->update([
                'status' => 'Deployed',
                'updated_at' => now()
            ]);
            
        \Log::info('RMM online status and server exclusions migration completed', [
            'timestamp' => now(),
            'actions' => [
                'Added rmm_online field to existing RMM assets',
                'Set servers to Deployed status (infrastructure, not assignable)'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert servers back to Ready To Deploy if needed
        DB::table('assets')
            ->where('type', 'Server')
            ->where('status', 'Deployed')
            ->update([
                'status' => 'Ready To Deploy',
                'updated_at' => now()
            ]);
    }
};
