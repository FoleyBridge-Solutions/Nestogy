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
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->constrained()->onDelete('cascade');
                        $table->foreignId('template_id')->nullable()->constrained('ticket_templates')->onDelete('set null');
                        $table->string('title');
                        $table->text('description');
                        $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'annually'])->default('monthly');
                        $table->integer('interval_value')->default(1);
                        $table->dateTime('next_run');
                        $table->dateTime('last_run')->nullable();
                        $table->enum('status', ['active', 'paused', 'completed'])->default('active');
                        $table->json('configuration')->nullable();
                        $table->timestamps();
                        $table->boolean('is_active')->default(true);
                        $table->date('next_run_date')->nullable();
                        $table->date('last_run_date')->nullable();
                        $table->string('name')->nullable();
                        $table->json('frequency_config')->nullable();
                        $table->date('end_date')->nullable();
                        $table->integer('max_occurrences')->nullable();
                        $table->integer('occurrences_count')->default(0);
                        $table->json('template_overrides')->nullable();

                        $table->index(['company_id', 'client_id']);
                        $table->index(['company_id', 'next_run']);
                        $table->index(['company_id', 'status']);
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
