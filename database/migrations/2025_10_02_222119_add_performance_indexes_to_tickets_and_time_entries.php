<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ticket_time_entries', function (Blueprint $table) {
            if (!$this->indexExists('ticket_time_entries', 'idx_time_entries_user_date')) {
                $table->index(['user_id', 'work_date'], 'idx_time_entries_user_date');
            }
            if (!$this->indexExists('ticket_time_entries', 'idx_time_entries_ticket_billable')) {
                $table->index(['ticket_id', 'is_billable'], 'idx_time_entries_ticket_billable');
            }
            if (!$this->indexExists('ticket_time_entries', 'idx_time_entries_company_date_billable')) {
                $table->index(['company_id', 'work_date', 'is_billable'], 'idx_time_entries_company_date_billable');
            }
            if (!$this->indexExists('ticket_time_entries', 'idx_time_entries_status_submitted')) {
                $table->index(['status', 'submitted_at'], 'idx_time_entries_status_submitted');
            }
        });

        Schema::table('ticket_comments', function (Blueprint $table) {
            if (!$this->indexExists('ticket_comments', 'idx_comments_ticket_created')) {
                $table->index(['ticket_id', 'created_at'], 'idx_comments_ticket_created');
            }
        });

        Schema::table('notifications', function (Blueprint $table) {
            if (!$this->indexExists('notifications', 'idx_notifications_notifiable_read')) {
                $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'idx_notifications_notifiable_read');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ticket_time_entries', function (Blueprint $table) {
            if ($this->indexExists('ticket_time_entries', 'idx_time_entries_user_date')) {
                $table->dropIndex('idx_time_entries_user_date');
            }
            if ($this->indexExists('ticket_time_entries', 'idx_time_entries_ticket_billable')) {
                $table->dropIndex('idx_time_entries_ticket_billable');
            }
            if ($this->indexExists('ticket_time_entries', 'idx_time_entries_company_date_billable')) {
                $table->dropIndex('idx_time_entries_company_date_billable');
            }
            if ($this->indexExists('ticket_time_entries', 'idx_time_entries_status_submitted')) {
                $table->dropIndex('idx_time_entries_status_submitted');
            }
        });

        Schema::table('ticket_comments', function (Blueprint $table) {
            if ($this->indexExists('ticket_comments', 'idx_comments_ticket_created')) {
                $table->dropIndex('idx_comments_ticket_created');
            }
        });

        Schema::table('notifications', function (Blueprint $table) {
            if ($this->indexExists('notifications', 'idx_notifications_notifiable_read')) {
                $table->dropIndex('idx_notifications_notifiable_read');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $exists = Schema::getConnection()->select(
            "SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?",
            [$table, $index]
        );
        
        return !empty($exists);
    }
};
