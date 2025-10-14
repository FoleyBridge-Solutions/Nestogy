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
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->string('from_status');
                        $table->string('to_status');
                        $table->string('transition_name');
                        $table->boolean('requires_approval')->default(false);
                        $table->json('allowed_roles')->nullable();
                        $table->timestamps();

                        $table->index(['company_id', 'from_status']);
                        $table->unique(['company_id', 'from_status', 'to_status'], 'ticket_status_unique');
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
