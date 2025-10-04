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
        Schema::table('quotes', function (Blueprint $table) {
            // Add missing timestamp columns
            $table->timestamp('sent_at')->nullable()->after('updated_at');
            $table->timestamp('viewed_at')->nullable()->after('sent_at');
            $table->timestamp('accepted_at')->nullable()->after('viewed_at');
            $table->timestamp('declined_at')->nullable()->after('accepted_at');
            
            // Indexes
            $table->index(['sent_at']);
            $table->index(['status', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['sent_at', 'viewed_at', 'accepted_at', 'declined_at']);
        });
    }
};
