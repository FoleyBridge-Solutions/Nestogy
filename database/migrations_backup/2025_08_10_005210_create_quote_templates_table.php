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
        Schema::create('quote_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name', 255)->unique(); // Template name
            $table->text('description')->nullable();
            $table->enum('category', [
                'voip_basic',
                'voip_premium', 
                'voip_enterprise',
                'phone_systems',
                'sip_trunks',
                'equipment',
                'maintenance',
                'custom'
            ]);
            $table->json('template_items')->nullable(); // Predefined line items
            $table->json('voip_config')->nullable(); // VoIP-specific configuration
            $table->json('default_pricing')->nullable(); // Default pricing structure
            $table->text('terms_conditions')->nullable(); // Default terms and conditions
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for performance
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'category']);
            $table->index('name');
            $table->index('category');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_templates');
    }
};
