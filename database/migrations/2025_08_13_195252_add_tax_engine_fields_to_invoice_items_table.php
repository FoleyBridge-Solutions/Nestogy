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
            // JSON field to store detailed tax breakdown from tax engine
            $table->json('tax_breakdown')->nullable()->after('tax');
            
            // JSON field to store service-specific data for tax calculations
            $table->json('service_data')->nullable()->after('tax_breakdown');
            
            // Tax rate percentage for backward compatibility and display
            $table->decimal('tax_rate', 8, 4)->nullable()->after('service_data');
            
            // Service type for tax engine routing
            $table->string('service_type', 50)->nullable()->after('tax_rate');
            
            // Tax jurisdiction ID for location-based taxes
            $table->unsignedBigInteger('tax_jurisdiction_id')->nullable()->after('service_type');
            
            // Foreign key for tax jurisdiction
            $table->foreign('tax_jurisdiction_id')->references('id')->on('tax_jurisdictions')->onDelete('set null');
            
            // Index for performance
            $table->index('service_type');
            $table->index('tax_jurisdiction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['tax_jurisdiction_id']);
            $table->dropIndex(['service_type']);
            $table->dropIndex(['tax_jurisdiction_id']);
            $table->dropColumn([
                'tax_breakdown',
                'service_data', 
                'tax_rate',
                'service_type',
                'tax_jurisdiction_id'
            ]);
        });
    }
};