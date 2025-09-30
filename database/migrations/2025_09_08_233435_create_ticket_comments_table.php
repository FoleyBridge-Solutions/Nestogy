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
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('company_id');
            $table->longText('content');
            $table->enum('visibility', ['public', 'internal'])->default('public');
            $table->enum('source', ['manual', 'workflow', 'system', 'api', 'email'])->default('manual');
            $table->unsignedBigInteger('author_id')->nullable();
            $table->enum('author_type', ['user', 'system', 'workflow', 'customer'])->default('user');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_resolution')->default(false);
            $table->unsignedBigInteger('time_entry_id')->nullable();

            // Sentiment analysis fields (matching ticket_replies)
            $table->decimal('sentiment_score', 3, 2)->nullable();
            $table->enum('sentiment_label', ['POSITIVE', 'WEAK_POSITIVE', 'NEUTRAL', 'WEAK_NEGATIVE', 'NEGATIVE'])->nullable();
            $table->timestamp('sentiment_analyzed_at')->nullable();
            $table->decimal('sentiment_confidence', 3, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('ticket_id');
            $table->index('company_id');
            $table->index('author_id');
            $table->index('visibility');
            $table->index('source');
            $table->index(['ticket_id', 'visibility']);
            $table->index(['company_id', 'ticket_id']);
            $table->index('parent_id');
            $table->index('is_resolution');
            $table->index('time_entry_id');
            $table->index(['sentiment_label', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
    }
};
