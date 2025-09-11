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
        Schema::create('email_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // INBOX, Sent, Drafts, Trash, Custom folders
            $table->string('path'); // Full IMAP path like INBOX.Clients.ProjectA
            $table->string('type')->default('custom'); // inbox, sent, drafts, trash, spam, custom
            $table->integer('message_count')->default(0);
            $table->integer('unread_count')->default(0);
            $table->boolean('is_subscribed')->default(true);
            $table->boolean('is_selectable')->default(true);
            $table->json('attributes')->nullable(); // IMAP folder attributes
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            
            $table->index(['email_account_id', 'type']);
            $table->unique(['email_account_id', 'path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_folders');
    }
};
