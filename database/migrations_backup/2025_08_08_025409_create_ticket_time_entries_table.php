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
        Schema::create('ticket_time_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('tenant_id');
            $table->text('description')->nullable();
            $table->decimal('hours_worked', 8, 2);
            $table->boolean('billable')->default(true);
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->date('work_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('entry_type', ['manual', 'timer', 'import'])->default('manual');
            $table->json('metadata')->nullable(); // Additional tracking data
            $table->timestamps();

            $table->index('ticket_id');
            $table->index(['user_id', 'work_date']);
            $table->index(['tenant_id', 'work_date']);
            $table->index(['tenant_id', 'billable']);
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_time_entries');
    }
};
