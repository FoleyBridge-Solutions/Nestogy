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
        // Add foreign keys to contacts table
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('set null');
        });

        // Add foreign key to locations table
        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropForeign(['vendor_id']);
            $table->dropColumn('location_id');
            $table->dropColumn('vendor_id');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropColumn('contact_id');
        });
    }
};