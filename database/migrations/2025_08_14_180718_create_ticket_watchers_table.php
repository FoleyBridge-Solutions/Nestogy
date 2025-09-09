<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the ticket_watchers table for tracking users and external emails
     * that are watching tickets for updates and notifications.
     * 
     * This table supports multi-tenancy through the company_id column
     * and uses the BelongsToCompany trait in the model.
     */
    public function up(): void
    {
        Schema::create('ticket_watchers', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Multi-tenancy column (required by BelongsToCompany trait)
            $table->unsignedBigInteger('company_id');
            
            // Foreign key to tickets table
            $table->unsignedBigInteger('ticket_id');
            
            // Foreign key to users table (nullable for external email watchers)
            $table->unsignedBigInteger('user_id')->nullable();
            
            // Email address for the watcher (required for both internal and external)
            $table->string('email', 255);
            
            // Who added this watcher (foreign key to users table)
            $table->unsignedBigInteger('added_by')->nullable();
            
            // JSON field for notification preferences
            // Stores boolean flags for different notification types:
            // - status_change, new_reply, assignment_change, priority_change, deadline_reminder, escalation
            $table->json('notification_preferences')->nullable();
            
            // Active status flag
            $table->boolean('is_active')->default(true);
            
            // Standard Laravel timestamps
            $table->timestamps();
            
            // Indexes for performance
            $table->index('company_id', 'idx_ticket_watchers_company');
            $table->index('ticket_id', 'idx_ticket_watchers_ticket');
            $table->index('user_id', 'idx_ticket_watchers_user');
            $table->index('email', 'idx_ticket_watchers_email');
            $table->index('added_by', 'idx_ticket_watchers_added_by');
            $table->index('is_active', 'idx_ticket_watchers_active');
            
            // Composite index for common queries
            $table->index(['company_id', 'ticket_id'], 'idx_ticket_watchers_company_ticket');
            $table->index(['ticket_id', 'is_active'], 'idx_ticket_watchers_ticket_active');
            
            // Unique constraint to prevent duplicate watchers
            // A watcher is unique by company, ticket, and email combination
            $table->unique(['company_id', 'ticket_id', 'email'], 'unique_ticket_watcher');
            
            // Foreign key constraints
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('cascade'); // Delete watchers when company is deleted
                  
            $table->foreign('ticket_id')
                  ->references('id')
                  ->on('tickets')
                  ->onDelete('cascade'); // Delete watchers when ticket is deleted
                  
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade'); // Delete watcher when user is deleted
                  
            $table->foreign('added_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null'); // Keep watcher but clear who added it
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the ticket_watchers table and all associated indexes and foreign keys.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_watchers');
    }
};