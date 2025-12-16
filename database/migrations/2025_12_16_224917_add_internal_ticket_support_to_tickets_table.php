<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds support for internal tickets (tickets without a client association).
     * Internal tickets are used for tracking internal work, admin tasks,
     * and other non-client-billable activities.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Add is_internal flag - true for internal tickets, false for client tickets
            $table->boolean('is_internal')->default(false)->after('billable');
        });

        // Make client_id nullable - internal tickets don't have a client
        // We need to do this in a separate statement for PostgreSQL
        Schema::table('tickets', function (Blueprint $table) {
            $table->bigInteger('client_id')->nullable()->change();
        });

        // Drop the existing foreign key and recreate it with nullable support
        Schema::table('tickets', function (Blueprint $table) {
            // The foreign key name may vary - try to drop it
            try {
                $table->dropForeign(['client_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist or have a different name
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            // Recreate the foreign key that allows null values
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, ensure no tickets have null client_id before making it NOT NULL
        // This would fail if there are internal tickets - you'd need to handle them first
        
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('is_internal');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->bigInteger('client_id')->nullable(false)->change();
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->cascadeOnDelete();
        });
    }
};
