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
        Schema::create('tax_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('calculable_type')->nullable();
            $table->unsignedBigInteger('calculable_id')->nullable();
            $table->string('calculation_id')->unique();
            $table->string('engine_type');
            $table->string('category_type')->nullable();
            $table->string('calculation_type');
            $table->decimal('base_amount', 15, 2);
            $table->integer('quantity')->default(1);
            $table->json('input_parameters');
            $table->json('customer_data')->nullable();
            $table->json('service_address')->nullable();
            $table->decimal('total_tax_amount', 15, 2);
            $table->decimal('final_amount', 15, 2);
            $table->decimal('effective_tax_rate', 8, 6);
            $table->json('tax_breakdown');
            $table->json('api_enhancements')->nullable();
            $table->json('jurisdictions')->nullable();
            $table->json('exemptions_applied')->nullable();
            $table->json('engine_metadata');
            $table->json('api_calls_made')->nullable();
            $table->boolean('validated')->default(false);
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('validation_notes')->nullable();
            $table->string('status');
            $table->json('status_history')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('change_log')->nullable();
            $table->integer('calculation_time_ms')->nullable();
            $table->integer('api_calls_count')->default(0);
            $table->decimal('api_cost', 10, 4)->default(0);
            $table->timestamps();
            
            $table->index(['calculable_type', 'calculable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_calculations');
    }
};
