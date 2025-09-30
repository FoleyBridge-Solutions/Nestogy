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
        Schema::create('ticket_priority_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->integer('queue_position')->default(1);
            $table->decimal('priority_score', 8, 2)->default(0);
            $table->integer('escalation_level')->default(0);
            $table->string('assigned_team')->nullable();
            $table->timestamp('sla_deadline')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->json('escalation_rules')->nullable();
            $table->text('escalation_reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'queue_position']);
            $table->index(['sla_deadline']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_priority_queues');
    }
};
