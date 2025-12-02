<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->text('ai_summary')->nullable()->after('notes');
            $table->string('ai_quality_score')->nullable()->after('ai_summary');
            $table->string('ai_conversion_likelihood')->nullable()->after('ai_quality_score');
            $table->text('ai_suggested_approach')->nullable()->after('ai_conversion_likelihood');
            $table->json('ai_key_insights')->nullable()->after('ai_suggested_approach');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_key_insights');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'ai_summary',
                'ai_quality_score',
                'ai_conversion_likelihood',
                'ai_suggested_approach',
                'ai_key_insights',
                'ai_analyzed_at',
            ]);
        });
    }
};
