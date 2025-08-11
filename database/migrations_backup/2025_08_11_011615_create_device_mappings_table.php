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
        Schema::create('device_mappings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('integration_id')->constrained()->onDelete('cascade');
            $table->string('rmm_device_id')->index();
            $table->foreignId('asset_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('device_name');
            $table->json('sync_data')->nullable(); // additional device data
            $table->timestamp('last_updated');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['integration_id', 'rmm_device_id']);
            $table->index(['client_id', 'is_active']);
            $table->index('last_updated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_mappings');
    }
};
