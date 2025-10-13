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
        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'idx_tickets_company_status');
            $table->index(['company_id', 'assigned_to', 'status'], 'idx_tickets_company_assigned_status');
            $table->index(['company_id', 'priority', 'status'], 'idx_tickets_company_priority_status');
            $table->index(['company_id', 'created_at'], 'idx_tickets_company_created');
            $table->index(['assigned_to', 'status'], 'idx_tickets_assigned_status');
            $table->index(['client_id', 'status'], 'idx_tickets_client_status');
            $table->index(['is_resolved', 'resolved_at'], 'idx_tickets_resolved');
            $table->index(['created_at', 'status'], 'idx_tickets_created_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('idx_tickets_company_status');
            $table->dropIndex('idx_tickets_company_assigned_status');
            $table->dropIndex('idx_tickets_company_priority_status');
            $table->dropIndex('idx_tickets_company_created');
            $table->dropIndex('idx_tickets_assigned_status');
            $table->dropIndex('idx_tickets_client_status');
            $table->dropIndex('idx_tickets_resolved');
            $table->dropIndex('idx_tickets_created_status');
        });
    }
};
