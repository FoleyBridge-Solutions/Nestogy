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
        Schema::create('contract_type_definitions', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();
            
            // Contract type definition
            $table->string('slug')->index(); // URL-friendly identifier
            $table->string('name'); // Display name
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable(); // UI color theme
            $table->json('config')->nullable(); // Type-specific configuration
            $table->json('default_values')->nullable(); // Default field values
            $table->json('business_rules')->nullable(); // Business logic rules
            $table->json('permissions')->nullable(); // Required permissions
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            // Indexes
            $table->index(['company_id', 'slug']);
            $table->index(['company_id', 'is_active']);
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
        Schema::dropIfExists('contract_type_definitions');
    }
};