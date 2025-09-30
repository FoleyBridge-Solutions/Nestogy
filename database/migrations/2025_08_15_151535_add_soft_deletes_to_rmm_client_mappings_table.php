<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds soft delete functionality to rmm_client_mappings table
     * for data retention and audit trail purposes.
     */
    public function up(): void
    {
        Schema::table('rmm_client_mappings', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Removes soft delete column from rmm_client_mappings table.
     */
    public function down(): void
    {
        Schema::table('rmm_client_mappings', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
