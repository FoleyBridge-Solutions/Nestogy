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
        Schema::create('ticket_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->string('from_status');
            $table->string('to_status');
            $table->string('required_role')->nullable(); // Role required to make this transition
            $table->boolean('requires_comment')->default(false);
            $table->text('auto_assign_rule')->nullable(); // JSON rules for auto-assignment
            $table->json('conditions')->nullable(); // Additional conditions for transition
            $table->json('actions')->nullable(); // Actions to perform on transition
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workflow_id', 'from_status']);
            $table->index(['workflow_id', 'is_active']);
            $table->foreign('workflow_id')->references('id')->on('ticket_workflows')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_status_transitions');
    }
};
