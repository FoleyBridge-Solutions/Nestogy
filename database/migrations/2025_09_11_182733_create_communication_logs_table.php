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
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type'); // inbound, outbound, internal, follow_up, meeting, etc.
            $table->string('channel'); // phone, email, sms, chat, in_person, video_call, etc.
            $table->string('contact_name')->nullable(); // For non-contact communications
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('subject');
            $table->text('notes');
            $table->boolean('follow_up_required')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->timestamps();
            
            $table->index(['client_id', 'created_at']);
            $table->index(['type', 'channel']);
            $table->index(['follow_up_required', 'follow_up_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
