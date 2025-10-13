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
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
                        $table->integer('priority_score');
                        $table->dateTime('queue_time');
                        $table->json('scoring_factors');
                        $table->boolean('is_escalated')->default(false);
                        $table->timestamps();

                        $table->index(['company_id', 'priority_score']);
                        $table->index(['company_id', 'queue_time']);
                        $table->unique(['company_id', 'ticket_id']);
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
