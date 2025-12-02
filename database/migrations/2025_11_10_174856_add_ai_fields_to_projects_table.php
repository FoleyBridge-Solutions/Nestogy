<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('ai_summary')->nullable()->after('description');
            $table->string('ai_risk_level')->nullable()->after('ai_summary');
            $table->decimal('ai_risk_confidence', 3, 2)->nullable()->after('ai_risk_level');
            $table->string('ai_progress_assessment')->nullable()->after('ai_risk_confidence');
            $table->json('ai_recommendations')->nullable()->after('ai_progress_assessment');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_recommendations');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'ai_summary',
                'ai_risk_level',
                'ai_risk_confidence',
                'ai_progress_assessment',
                'ai_recommendations',
                'ai_analyzed_at',
            ]);
        });
    }
};
