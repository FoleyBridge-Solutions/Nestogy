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
        Schema::table('locations', function (Blueprint $table) {
            // Add composite index for search optimization
            $table->index(['client_id', 'name'], 'locations_client_search_name_index');
            $table->index(['client_id', 'city'], 'locations_client_search_city_index');
            $table->index(['client_id', 'state'], 'locations_client_search_state_index');

            // Add composite index for filtering
            $table->index(['client_id', 'state', 'country'], 'locations_client_filter_index');

            // Add index for ordering
            $table->index(['client_id', 'primary', 'name'], 'locations_client_order_index');

            // Add index for address search (since it's the most commonly searched field)
            $table->index(['client_id', 'address'], 'locations_client_address_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // Drop the indexes in reverse order
            $table->dropIndex('locations_client_address_index');
            $table->dropIndex('locations_client_order_index');
            $table->dropIndex('locations_client_filter_index');
            $table->dropIndex('locations_client_search_state_index');
            $table->dropIndex('locations_client_search_city_index');
            $table->dropIndex('locations_client_search_name_index');
        });
    }
};
