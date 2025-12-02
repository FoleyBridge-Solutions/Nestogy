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
            $table->text('ai_summary')->nullable()->after('resolution_summary');
            $table->text('ai_sentiment')->nullable()->after('ai_summary');
            $table->string('ai_category')->nullable()->after('ai_sentiment');
            $table->integer('ai_category_confidence')->nullable()->after('ai_category');
            $table->string('ai_priority_suggestion')->nullable()->after('ai_category_confidence');
            $table->text('ai_suggestions')->nullable()->after('ai_priority_suggestion');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_suggestions');
            
            $table->index('ai_analyzed_at');
            $table->index(['company_id', 'ai_analyzed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['tickets_ai_analyzed_at_index']);
            $table->dropIndex(['tickets_company_id_ai_analyzed_at_index']);
            
            $table->dropColumn([
                'ai_summary',
                'ai_sentiment',
                'ai_category',
                'ai_category_confidence',
                'ai_priority_suggestion',
                'ai_suggestions',
                'ai_analyzed_at',
            ]);
        });
    }
};
