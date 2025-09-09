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
        Schema::create('contract_status_transitions', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();
            
            // Transition definition
            $table->string('from_status_slug')->index();
            $table->string('to_status_slug')->index();
            $table->string('label')->nullable(); // Display label for the transition
            $table->text('description')->nullable();
            $table->json('conditions')->nullable(); // Conditions that must be met
            $table->json('required_permissions')->nullable(); // Required permissions
            $table->json('required_fields')->nullable(); // Fields that must be filled
            $table->json('actions')->nullable(); // Actions to execute on transition
            $table->json('notifications')->nullable(); // Notifications to send
            $table->boolean('requires_confirmation')->default(false);
            $table->string('confirmation_message')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            // Indexes
            $table->index(['company_id', 'from_status_slug']);
            $table->index(['company_id', 'to_status_slug']);
            $table->index(['company_id', 'is_active']);
            
            // Unique constraint
            $table->unique(['company_id', 'from_status_slug', 'to_status_slug'], 'unique_status_transition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_status_transitions');
    }
};