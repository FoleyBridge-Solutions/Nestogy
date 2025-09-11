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
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_folder_id')->constrained()->cascadeOnDelete();
            
            // Message identifiers
            $table->string('message_id'); // RFC Message-ID
            $table->string('uid'); // IMAP UID
            $table->string('thread_id')->nullable(); // For conversation threading
            $table->foreignId('reply_to_message_id')->nullable()->constrained('email_messages');
            
            // Headers
            $table->string('subject')->nullable();
            $table->text('from_address');
            $table->string('from_name')->nullable();
            $table->text('to_addresses'); // JSON array
            $table->text('cc_addresses')->nullable(); // JSON array  
            $table->text('bcc_addresses')->nullable(); // JSON array
            $table->text('reply_to_addresses')->nullable(); // JSON array
            
            // Content
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->text('preview')->nullable(); // First 200 chars for quick display
            
            // Metadata
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->integer('size_bytes')->default(0);
            $table->string('priority')->default('normal'); // high, normal, low
            $table->boolean('is_read')->default(false);
            $table->boolean('is_flagged')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->boolean('is_answered')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->boolean('has_attachments')->default(false);
            
            // Integration flags
            $table->boolean('is_ticket_created')->default(false);
            $table->foreignId('ticket_id')->nullable()->constrained('tickets');
            $table->boolean('is_communication_logged')->default(false);
            $table->foreignId('communication_log_id')->nullable()->constrained('communication_logs');
            
            // Raw data
            $table->json('headers')->nullable(); // Full email headers
            $table->json('flags')->nullable(); // IMAP flags
            
            $table->timestamps();
            
            $table->index(['email_account_id', 'email_folder_id']);
            $table->index(['message_id']);
            $table->index(['uid', 'email_account_id']);
            $table->index(['thread_id']);
            $table->index(['is_read', 'received_at']);
            $table->index(['sent_at']);
            $table->fullText(['subject', 'body_text', 'from_name', 'from_address']);
            $table->unique(['email_account_id', 'uid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
