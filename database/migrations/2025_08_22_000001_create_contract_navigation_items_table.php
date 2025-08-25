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
        Schema::create('contract_navigation_items', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();
            
            // Navigation configuration
            $table->string('slug')->index();
            $table->string('label');
            $table->string('icon')->nullable();
            $table->string('route')->nullable();
            $table->string('parent_slug')->nullable()->index();
            $table->integer('sort_order')->default(0);
            $table->json('permissions')->nullable(); // Required permissions to see this item
            $table->json('conditions')->nullable(); // Conditions for when to show this item
            $table->json('config')->nullable(); // Additional configuration
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            // Indexes
            $table->index(['company_id', 'slug']);
            $table->index(['company_id', 'parent_slug']);
            $table->index(['company_id', 'sort_order']);
            $table->index(['company_id', 'is_active']);
            
            // Unique constraint
            $table->unique(['company_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_navigation_items');
    }
};