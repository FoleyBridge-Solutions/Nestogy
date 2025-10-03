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
        Schema::create('compliance_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('compliance_requirement_id')->nullable();
            $table->string('check_type')->nullable();
            $table->string('status')->default('active');
            $table->string('findings')->nullable();
            $table->string('recommendations')->nullable();
            $table->string('evidence_documents')->nullable();
            $table->string('checked_by')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('next_check_date')->nullable();
            $table->string('compliance_score')->nullable();
            $table->string('risk_level')->nullable();
            $table->string('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes('archived_at');
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_checks');
    }
};
