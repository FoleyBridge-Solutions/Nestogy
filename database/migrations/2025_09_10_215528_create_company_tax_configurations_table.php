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
        Schema::create('company_tax_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('state_code', 3); // TX, CA, NY, etc.
            $table->string('state_name'); // Texas, California, New York, etc.
            $table->string('service_class'); // Full class name of the service
            $table->text('api_key')->nullable();
            $table->string('api_base_url')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('auto_update_enabled')->default(false);
            $table->string('update_frequency')->default('quarterly'); // daily, weekly, monthly, quarterly
            $table->timestamp('last_updated')->nullable();
            $table->timestamp('last_successful_update')->nullable();
            $table->json('metadata')->nullable(); // Additional configuration
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'state_code']);
            $table->index(['company_id', 'is_enabled']);
            $table->index('state_code');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_tax_configurations');
    }
};
