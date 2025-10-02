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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('ticket_created')->default(true);
            $table->boolean('ticket_assigned')->default(true);
            $table->boolean('ticket_status_changed')->default(true);
            $table->boolean('ticket_resolved')->default(true);
            $table->boolean('ticket_comment_added')->default(true);
            $table->boolean('sla_breach_warning')->default(true);
            $table->boolean('sla_breached')->default(true);
            $table->boolean('daily_digest')->default(false);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('in_app_enabled')->default(true);
            $table->time('digest_time')->default('08:00');
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
