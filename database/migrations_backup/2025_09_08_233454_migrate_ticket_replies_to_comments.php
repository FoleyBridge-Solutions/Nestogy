<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate all ticket_replies to ticket_comments
        DB::statement("
            INSERT INTO ticket_comments (
                ticket_id,
                company_id,
                content,
                visibility,
                source,
                author_id,
                author_type,
                metadata,
                parent_id,
                is_resolution,
                time_entry_id,
                sentiment_score,
                sentiment_label,
                sentiment_analyzed_at,
                sentiment_confidence,
                created_at,
                updated_at,
                deleted_at
            )
            SELECT 
                ticket_id,
                company_id,
                reply as content,
                CASE 
                    WHEN type = 'public' THEN 'public'
                    ELSE 'internal'
                END as visibility,
                'manual' as source,
                replied_by as author_id,
                'user' as author_type,
                NULL as metadata,
                NULL as parent_id,
                FALSE as is_resolution,
                NULL as time_entry_id,
                sentiment_score,
                sentiment_label,
                sentiment_analyzed_at,
                sentiment_confidence,
                created_at,
                updated_at,
                archived_at as deleted_at
            FROM ticket_replies
        ");

        // Create time entries for replies that had time_worked
        $repliesWithTime = DB::table('ticket_replies')
            ->whereNotNull('time_worked')
            ->where('time_worked', '!=', '00:00:00')
            ->get();

        foreach ($repliesWithTime as $reply) {
            // Convert TIME to hours
            $timeParts = explode(':', $reply->time_worked);
            $hours = intval($timeParts[0]) + (intval($timeParts[1]) / 60);

            if ($hours > 0) {
                // Create time entry
                $timeEntryId = DB::table('ticket_time_entries')->insertGetId([
                    'ticket_id' => $reply->ticket_id,
                    'user_id' => $reply->replied_by,
                    'company_id' => $reply->company_id,
                    'description' => 'Time logged with comment',
                    'hours_worked' => $hours,
                    'billable' => true,
                    'work_date' => date('Y-m-d', strtotime($reply->created_at)),
                    'entry_type' => 'manual',
                    'work_type' => 'general_support',
                    'status' => 'approved',
                    'created_at' => $reply->created_at,
                    'updated_at' => $reply->updated_at,
                ]);

                // Update the migrated comment to link to time entry
                DB::table('ticket_comments')
                    ->where('ticket_id', $reply->ticket_id)
                    ->where('author_id', $reply->replied_by)
                    ->where('created_at', $reply->created_at)
                    ->update(['time_entry_id' => $timeEntryId]);
            }
        }

        // Add column to track migration (for safety)
        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->boolean('migrated_to_comments')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete migrated comments
        DB::table('ticket_comments')->where('source', 'manual')->delete();

        // Remove migration tracking column
        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->dropColumn('migrated_to_comments');
        });
    }
};
