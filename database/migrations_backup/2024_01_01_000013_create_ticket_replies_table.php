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
        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->longText('reply');
            $table->string('type', 10); // public, private, internal
            $table->time('time_worked')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('replied_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');

            // Indexes
            $table->index('ticket_id');
            $table->index('replied_by');
            $table->index('type');
            $table->index(['ticket_id', 'type']);
            $table->index('time_worked');
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
    }
};