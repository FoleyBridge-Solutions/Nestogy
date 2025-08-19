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
        // Fix legacy asset statuses that were incorrectly mapped from RMM online/offline
        // Convert active -> Ready To Deploy (available for assignment)
        // Convert inactive -> Ready To Deploy (also available, just not currently connected)
        
        DB::table('assets')
            ->where('status', 'active')
            ->update([
                'status' => 'Ready To Deploy',
                'updated_at' => now()
            ]);
            
        DB::table('assets')
            ->where('status', 'inactive')
            ->update([
                'status' => 'Ready To Deploy', 
                'updated_at' => now()
            ]);
            
        // Log the migration for tracking
        \Log::info('Legacy asset statuses migration completed', [
            'timestamp' => now(),
            'action' => 'Converted active/inactive statuses to Ready To Deploy',
            'note' => 'RMM connectivity status is now stored in notes field as rmm_online'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If we need to rollback (though this shouldn't be necessary)
        // We can't reliably restore the original active/inactive statuses
        // since they were incorrectly based on RMM connectivity
        \Log::warning('Asset status migration rollback attempted - no action taken', [
            'timestamp' => now(),
            'note' => 'Cannot reliably restore legacy active/inactive statuses as they were incorrect'
        ]);
    }
};
