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
        Schema::table('invoice_items', function (Blueprint $table) {
            // VoIP service type for tax calculation
            $table->string('service_type', 50)->nullable()->after('product_id');
            
            // Link to VoIP tax category
            $table->unsignedBigInteger('tax_category_id')->nullable()->after('service_type');
            
            // Store detailed VoIP tax calculation data
            $table->json('voip_tax_data')->nullable()->after('tax_category_id');
            
            // VoIP-specific metrics for tax calculation
            $table->unsignedInteger('line_count')->nullable()->after('voip_tax_data');
            $table->unsignedInteger('minutes')->nullable()->after('line_count');
            
            // Indexes for performance
            $table->index('service_type');
            $table->index('tax_category_id');
            
            // Foreign key constraint
            $table->foreign('tax_category_id')->references('id')->on('tax_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['tax_category_id']);
            $table->dropIndex(['service_type']);
            $table->dropIndex(['tax_category_id']);
            $table->dropColumn([
                'service_type',
                'tax_category_id', 
                'voip_tax_data',
                'line_count',
                'minutes'
            ]);
        });
    }
};
