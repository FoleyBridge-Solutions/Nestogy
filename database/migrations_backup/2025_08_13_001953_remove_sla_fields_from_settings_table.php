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
        Schema::table('settings', function (Blueprint $table) {
            // Remove SLA-related fields that are now in the dedicated slas table
            $table->dropColumn([
                'sla_definitions',
                'sla_escalation_policies',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Re-add the SLA fields if rollback is needed
            $table->json('sla_definitions')->nullable();
            $table->json('sla_escalation_policies')->nullable();
        });
    }
};
