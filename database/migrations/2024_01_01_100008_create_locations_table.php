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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('country')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('phone')->nullable();
            $table->string('hours')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('contact_id')->nullable();

            // Indexes
            $table->index('name');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('primary');
            $table->index(['client_id', 'primary']);
            $table->index(['company_id', 'client_id']);
            $table->index('archived_at');

            // Search optimization indexes
            $table->index(['client_id', 'name'], 'locations_client_search_name_index');
            $table->index(['client_id', 'city'], 'locations_client_search_city_index');
            $table->index(['client_id', 'state'], 'locations_client_search_state_index');
            $table->index(['client_id', 'state', 'country'], 'locations_client_filter_index');
            $table->index(['client_id', 'primary', 'name'], 'locations_client_order_index');
            $table->index(['client_id', 'address'], 'locations_client_address_index');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            // contact_id foreign key will be added after contacts table is created
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
