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
        Schema::create('recurring_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('client_id');
            $table->string('name');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->integer('interval_value')->default(1);
            $table->json('frequency_config')->nullable(); // For complex scheduling
            $table->date('next_run_date');
            $table->date('last_run_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('max_occurrences')->nullable();
            $table->integer('occurrences_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('template_overrides')->nullable(); // Override template values
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'next_run_date']);
            $table->index(['tenant_id', 'is_active']);
            $table->foreign('template_id')->references('id')->on('ticket_templates')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_tickets');
    }
};
