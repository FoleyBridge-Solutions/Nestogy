<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_messages', function (Blueprint $table) {
            $table->text('ai_summary')->nullable()->after('preview');
            $table->string('ai_sentiment')->nullable()->after('ai_summary');
            $table->string('ai_priority')->nullable()->after('ai_sentiment');
            $table->text('ai_suggested_reply')->nullable()->after('ai_priority');
            $table->json('ai_action_items')->nullable()->after('ai_suggested_reply');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_action_items');
        });
    }

    public function down(): void
    {
        Schema::table('email_messages', function (Blueprint $table) {
            $table->dropColumn([
                'ai_summary',
                'ai_sentiment',
                'ai_priority',
                'ai_suggested_reply',
                'ai_action_items',
                'ai_analyzed_at',
            ]);
        });
    }
};
