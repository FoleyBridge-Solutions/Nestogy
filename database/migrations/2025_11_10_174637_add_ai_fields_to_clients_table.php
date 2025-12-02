<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->text('ai_summary')->nullable()->after('notes');
            $table->string('ai_health_score')->nullable()->after('ai_summary');
            $table->string('ai_risk_level')->nullable()->after('ai_health_score');
            $table->decimal('ai_risk_confidence', 3, 2)->nullable()->after('ai_risk_level');
            $table->string('ai_client_type')->nullable()->after('ai_risk_confidence');
            $table->json('ai_insights')->nullable()->after('ai_client_type');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_insights');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'ai_summary',
                'ai_health_score',
                'ai_risk_level',
                'ai_risk_confidence',
                'ai_client_type',
                'ai_insights',
                'ai_analyzed_at',
            ]);
        });
    }
};
