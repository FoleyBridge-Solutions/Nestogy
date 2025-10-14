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
        Schema::create('client_calendar_events', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->constrained()->onDelete('cascade');
                        $table->string('title');
                        $table->text('description')->nullable();
                        $table->dateTime('start_time');
                        $table->dateTime('end_time');
                        $table->boolean('all_day')->default(false);
                        $table->enum('type', ['maintenance', 'meeting', 'project', 'other'])->default('other');
                        $table->json('attendees')->nullable();
                        $table->timestamps();

                        $table->index(['company_id', 'client_id']);
                        $table->index(['company_id', 'start_time']);
                        $table->index(['company_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_calendar_events');
    }
};
