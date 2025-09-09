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
        Schema::table('tickets', function (Blueprint $table) {
            // Sentiment analysis fields
            $table->decimal('sentiment_score', 3, 2)->nullable()->comment('Sentiment score from -1.00 (negative) to 1.00 (positive)');
            $table->enum('sentiment_label', ['POSITIVE', 'WEAK_POSITIVE', 'NEUTRAL', 'WEAK_NEGATIVE', 'NEGATIVE'])->nullable()->comment('Sentiment classification label');
            $table->timestamp('sentiment_analyzed_at')->nullable()->comment('When sentiment analysis was performed');
            $table->decimal('sentiment_confidence', 3, 2)->nullable()->comment('Confidence score for sentiment analysis (0.00 to 1.00)');
            
            // Add index for sentiment-based queries
            $table->index(['sentiment_label', 'created_at'], 'idx_tickets_sentiment_created');
            $table->index(['sentiment_score', 'company_id'], 'idx_tickets_sentiment_company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('idx_tickets_sentiment_created');
            $table->dropIndex('idx_tickets_sentiment_company');
            $table->dropColumn([
                'sentiment_score',
                'sentiment_label', 
                'sentiment_analyzed_at',
                'sentiment_confidence'
            ]);
        });
    }
};