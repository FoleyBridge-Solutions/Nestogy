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
        Schema::create('contract_status_definitions', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();
            
            // Status definition
            $table->string('slug')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->default('#6b7280'); // UI color
            $table->string('icon')->nullable();
            $table->boolean('is_initial')->default(false); // Can be set on creation
            $table->boolean('is_final')->default(false); // Terminal status
            $table->json('config')->nullable(); // Status-specific configuration
            $table->json('permissions')->nullable(); // Permissions to set this status
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            // Indexes
            $table->index(['company_id', 'slug']);
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'is_initial']);
            $table->index(['company_id', 'is_final']);
            $table->index(['company_id', 'sort_order']);
            
            // Unique constraint
            $table->unique(['company_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_status_definitions');
    }
};