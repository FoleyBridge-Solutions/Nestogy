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
        Schema::table('compliance_checks', function (Blueprint $table) {
            $table->foreignId('compliance_requirement_id')->nullable()->after('company_id');
            $table->string('check_type')->default('manual')->after('name');
            $table->string('status')->default('pending')->after('check_type');
            $table->text('findings')->nullable()->after('status');
            $table->json('recommendations')->nullable()->after('findings');
            $table->json('evidence_documents')->nullable()->after('recommendations');
            $table->foreignId('checked_by')->nullable()->after('evidence_documents');
            $table->timestamp('checked_at')->nullable()->after('checked_by');
            $table->timestamp('next_check_date')->nullable()->after('checked_at');
            $table->decimal('compliance_score', 5, 2)->nullable()->after('next_check_date');
            $table->string('risk_level')->default('low')->after('compliance_score');
            $table->json('metadata')->nullable()->after('risk_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compliance_checks', function (Blueprint $table) {
            $table->dropColumn([
                'compliance_requirement_id',
                'check_type',
                'status',
                'findings',
                'recommendations',
                'evidence_documents',
                'checked_by',
                'checked_at',
                'next_check_date',
                'compliance_score',
                'risk_level',
                'metadata'
            ]);
        });
    }
};
