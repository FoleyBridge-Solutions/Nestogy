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
        Schema::create('dashboard_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('metric_key');
            $table->decimal('value', 20, 4);
            $table->decimal('previous_value', 20, 4)->nullable();
            $table->decimal('change_percentage', 8, 2)->nullable();
            $table->string('trend')->nullable(); // up, down, stable
            $table->json('breakdown')->nullable(); // Detailed breakdown
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['company_id', 'metric_key', 'calculated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_metrics');
    }
};
