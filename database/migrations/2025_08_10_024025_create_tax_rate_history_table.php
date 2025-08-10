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
        Schema::create('tax_rate_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('voip_tax_rate_id');
            
            // Change data
            $table->json('old_values'); // Previous values
            $table->json('new_values'); // New values
            $table->json('changed_fields')->nullable(); // List of fields that changed
            
            // Change metadata
            $table->string('change_reason')->nullable();
            $table->text('change_description')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('source', 100)->nullable(); // manual, api, import, etc.
            $table->string('batch_id', 100)->nullable(); // For bulk operations
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'voip_tax_rate_id']);
            $table->index('voip_tax_rate_id');
            $table->index('changed_by');
            $table->index('source');
            $table->index('batch_id');
            $table->index('created_at');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('voip_tax_rate_id')->references('id')->on('voip_tax_rates')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rate_history');
    }
};
