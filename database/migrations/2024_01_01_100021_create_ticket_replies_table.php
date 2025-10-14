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
            $table->unsignedBigInteger('company_id');
            $table->longText('reply');
            $table->string('type', 10); // public, private, internal
            $table->time('time_worked')->nullable();
            $table->timestamps();
            $table->decimal('sentiment_score', 3, 2)->nullable()->comment('Sentiment score from -1.00 (negative) to 1.00 (positive)');
            $table->enum('sentiment_label', ['POSITIVE', 'WEAK_POSITIVE', 'NEUTRAL', 'WEAK_NEGATIVE', 'NEGATIVE'])->nullable()->comment('Sentiment classification label');
            $table->timestamp('sentiment_analyzed_at')->nullable()->comment('When sentiment analysis was performed');
            $table->decimal('sentiment_confidence', 3, 2)->nullable()->comment('Confidence score for sentiment analysis (0.00 to 1.00)');
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('replied_by');
            $table->unsignedBigInteger('ticket_id');

            // Indexes
            $table->index('ticket_id');
            $table->index('company_id');
            $table->index('replied_by');
            $table->index('type');
            $table->index(['ticket_id', 'type']);
            $table->index(['sentiment_label', 'created_at'], 'idx_ticket_replies_sentiment_created');
            $table->index(['sentiment_score', 'ticket_id'], 'idx_ticket_replies_sentiment_ticket');
            $table->index(['company_id', 'ticket_id']);
            $table->index('time_worked');
            $table->index('archived_at');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('replied_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
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
