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
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->string('name', 255);
                        $table->text('description')->nullable();
                        $table->enum('category', [
                            'basic',
                            'standard',
                            'premium',
                            'enterprise',
                            'custom',
                            'equipment',
                            'maintenance',
                            'professional',
                            'managed',
                        ]);
                        $table->json('template_items')->nullable(); // Predefined line items
                        $table->json('service_config')->nullable(); // Service-specific configuration
                        $table->json('pricing_config')->nullable(); // Pricing structure
                        $table->json('tax_config')->nullable(); // Tax configuration
                        $table->text('terms_conditions')->nullable(); // Default terms and conditions
                        $table->boolean('is_active')->default(true);
                        $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                        $table->timestamps();
                        $table->timestamp('archived_at')->nullable();

                        // Indexes for performance
                        $table->index(['company_id', 'is_active']);
                        $table->index(['company_id', 'category']);
                        $table->index('name');
                        $table->index('category');
                        $table->index('created_by');
                        $table->unique(['company_id', 'name']); // Unique template names per company
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
