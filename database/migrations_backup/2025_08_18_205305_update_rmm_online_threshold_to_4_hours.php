<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update RMM online status using 4-hour threshold (more appropriate for business environment)
        $assets = \DB::table('assets')
            ->whereNotNull('notes')
            ->get();

        $updatedCount = 0;

        foreach ($assets as $asset) {
            $rmmData = json_decode($asset->notes, true);

            if ($rmmData && isset($rmmData['rmm_last_seen'])) {
                try {
                    $lastSeen = \Carbon\Carbon::parse($rmmData['rmm_last_seen']);
                    // Consider online if last seen within 4 hours (240 minutes)
                    $isOnline = $lastSeen->diffInMinutes() < 240;

                    // Only update if the online status has changed
                    if (($rmmData['rmm_online'] ?? false) !== $isOnline) {
                        $rmmData['rmm_online'] = $isOnline;

                        \DB::table('assets')
                            ->where('id', $asset->id)
                            ->update([
                                'notes' => json_encode($rmmData),
                                'updated_at' => now(),
                            ]);

                        $updatedCount++;
                    }
                } catch (\Exception $e) {
                    // Skip assets with invalid timestamps
                    continue;
                }
            }
        }

        \Log::info('RMM online threshold updated to 4 hours', [
            'timestamp' => now(),
            'assets_updated' => $updatedCount,
            'new_threshold' => '240 minutes (4 hours)',
            'reason' => 'More appropriate for business environment where devices may be offline during breaks/EOD',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to 10-minute threshold if needed
        $assets = \DB::table('assets')
            ->whereNotNull('notes')
            ->get();

        foreach ($assets as $asset) {
            $rmmData = json_decode($asset->notes, true);

            if ($rmmData && isset($rmmData['rmm_last_seen'])) {
                try {
                    $lastSeen = \Carbon\Carbon::parse($rmmData['rmm_last_seen']);
                    // Revert to 10-minute threshold
                    $isOnline = $lastSeen->diffInMinutes() < 10;
                    $rmmData['rmm_online'] = $isOnline;

                    \DB::table('assets')
                        ->where('id', $asset->id)
                        ->update([
                            'notes' => json_encode($rmmData),
                            'updated_at' => now(),
                        ]);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }
};
