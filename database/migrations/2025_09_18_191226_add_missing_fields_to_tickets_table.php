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
            // Add missing fields if they don't exist
            if (!Schema::hasColumn('tickets', 'type')) {
                $table->string('type')->nullable()->after('priority');
            }
            if (!Schema::hasColumn('tickets', 'closed_at')) {
                $table->timestamp('closed_at')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'first_response_at')) {
                $table->timestamp('first_response_at')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'satisfaction_rating')) {
                $table->integer('satisfaction_rating')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'time_spent')) {
                $table->integer('time_spent')->default(0);
            }
            if (!Schema::hasColumn('tickets', 'billable')) {
                $table->boolean('billable')->default(false);
            }
            if (!Schema::hasColumn('tickets', 'tags')) {
                $table->json('tags')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['type', 'closed_at', 'first_response_at', 'satisfaction_rating', 'time_spent', 'billable', 'tags']);
        });
    }
};
