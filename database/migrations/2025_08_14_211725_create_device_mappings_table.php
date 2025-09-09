<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration creates the device_mappings table which maps devices 
     * from RMM systems to internal assets and clients. It handles device
     * synchronization and identification across integrated systems.
     */
    public function up(): void
    {
        Schema::create('device_mappings', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // UUID for public-facing identification (security)
            $table->uuid('uuid')->unique();
            
            // Foreign key to rmm_integrations table
            $table->unsignedBigInteger('integration_id');
            $table->foreign('integration_id')
                  ->references('id')
                  ->on('rmm_integrations')
                  ->onDelete('cascade'); // Delete mappings when integration is deleted
            
            // RMM system's device identifier
            $table->string('rmm_device_id');
            
            // Nullable foreign key to assets table (device may not be mapped yet)
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->foreign('asset_id')
                  ->references('id')
                  ->on('assets')
                  ->onDelete('set null'); // Keep mapping but unlink if asset is deleted
            
            // Foreign key to clients table
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')
                  ->references('id')
                  ->on('clients')
                  ->onDelete('cascade'); // Delete mappings when client is deleted
            
            // Device name for display and identification
            $table->string('device_name');
            
            // JSON column for storing RMM payload data and sync metadata
            $table->json('sync_data')->nullable();
            
            // Track when device was last synchronized
            $table->timestamp('last_updated');
            
            // Active status for soft disabling without deletion
            $table->boolean('is_active')->default(true);
            
            // Standard Laravel timestamps
            $table->timestamps();
            
            // Composite index for fast lookups during sync operations
            // This ensures we can quickly find a device by integration and RMM ID
            $table->index(['integration_id', 'rmm_device_id'], 'idx_integration_rmm_device');
            
            // Index for client-based queries (e.g., listing all devices for a client)
            $table->index('client_id', 'idx_client');
            
            // Index for asset-based queries (e.g., finding mapping by asset)
            $table->index('asset_id', 'idx_asset');
            
            // Index for filtering active/inactive devices
            $table->index('is_active', 'idx_active_status');
            
            // Composite index for active devices by client (common query pattern)
            $table->index(['client_id', 'is_active'], 'idx_client_active');
            
            // Index for performance on last_updated queries (stale device checks)
            $table->index('last_updated', 'idx_last_updated');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * WARNING: This will permanently delete all device mapping data.
     * Ensure data is backed up or migrated before running rollback.
     */
    public function down(): void
    {
        // Drop indexes first (Laravel handles this automatically with dropIfExists)
        // Foreign key constraints are also automatically dropped
        
        Schema::dropIfExists('device_mappings');
    }
};