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
        Schema::table('physical_mail_orders', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('to_postal_code');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('formatted_address')->nullable()->after('longitude');
            $table->index(['latitude', 'longitude'], 'physical_mail_orders_location_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('physical_mail_orders', function (Blueprint $table) {
            $table->dropIndex('physical_mail_orders_location_index');
            $table->dropColumn(['latitude', 'longitude', 'formatted_address']);
        });
    }
};
