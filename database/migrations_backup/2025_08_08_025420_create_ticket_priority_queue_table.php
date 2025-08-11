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
        Schema::create('ticket_priority_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('tenant_id');
            $table->integer('queue_position');
            $table->decimal('priority_score', 8, 2)->default(0);
            $table->integer('escalation_level')->default(0);
            $table->string('assigned_team')->nullable();
            $table->timestamp('sla_deadline')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->json('escalation_rules')->nullable(); // Auto-escalation configuration
            $table->text('escalation_reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('ticket_id'); // Each ticket can only be in queue once
            $table->index(['tenant_id', 'queue_position']);
            $table->index(['tenant_id', 'priority_score']);
            $table->index(['tenant_id', 'sla_deadline']);
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_priority_queue');
    }
};
