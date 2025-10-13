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
        Schema::table('tickets', function (Blueprint $table) {
            // Resolution status fields
            $table->boolean('is_resolved')->default(false)->after('status');
            $table->timestamp('resolved_at')->nullable()->after('is_resolved');
            $table->unsignedBigInteger('resolved_by')->nullable()->after('resolved_at');
            $table->text('resolution_summary')->nullable()->after('resolved_by');

            // Client reopening control
            $table->boolean('client_can_reopen')->default(true)->after('resolution_summary');
            $table->timestamp('reopened_at')->nullable()->after('client_can_reopen');
            $table->unsignedBigInteger('reopened_by')->nullable()->after('reopened_at');
            $table->integer('resolution_count')->default(0)->after('reopened_by');

            // Indexes
            $table->index('is_resolved');
            $table->index(['is_resolved', 'status']);
            $table->index('resolved_at');
        });

        // Migrate existing "Resolved" status tickets
        DB::statement("UPDATE tickets SET is_resolved = true, resolved_at = updated_at, status = 'Closed' WHERE status = 'Resolved'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore "Resolved" status for tickets that were resolved
        DB::statement("UPDATE tickets SET status = 'Resolved' WHERE is_resolved = true AND status = 'Closed'");

        Schema::table('tickets', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['is_resolved', 'status']);
            $table->dropIndex(['is_resolved']);
            $table->dropIndex(['resolved_at']);

            // Drop columns
            $table->dropColumn([
                'is_resolved',
                'resolved_at',
                'resolved_by',
                'resolution_summary',
                'client_can_reopen',
                'reopened_at',
                'reopened_by',
                'resolution_count',
            ]);
        });
    }
};
