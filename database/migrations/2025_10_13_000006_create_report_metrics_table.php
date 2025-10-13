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
        Schema::create('report_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('metric_name');
            $table->string('metric_type');
            $table->decimal('value', 15, 4);
            $table->json('dimensions')->nullable();
            $table->date('metric_date');
            $table->timestamps();

            $table->index(['company_id', 'metric_name']);
            $table->index(['company_id', 'metric_date']);
            $table->index(['company_id', 'metric_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_metrics');
    }
};
