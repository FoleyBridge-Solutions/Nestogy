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
        Schema::create('settings_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('domain', 50);     // 'company', 'communication', 'financial', etc.
            $table->string('category', 50);   // 'general', 'email', 'billing', etc.
            $table->json('settings');         // Flexible JSON storage for settings
            $table->json('metadata')->nullable(); // Additional metadata (last tested, etc.)
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_modified_at')->nullable();
            $table->unsignedBigInteger('last_modified_by')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'domain']);
            $table->index(['company_id', 'domain', 'category']);
            $table->unique(['company_id', 'domain', 'category'], 'unique_company_domain_category');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('last_modified_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings_configurations');
    }
};
