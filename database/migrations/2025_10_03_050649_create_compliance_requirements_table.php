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
        Schema::create('compliance_requirements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('requirement_type')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('priority')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_checked_at')->nullable();
            $table->string('requirements_data')->nullable();
            $table->string('compliance_criteria')->nullable();
            $table->string('created_by')->nullable();
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
        Schema::dropIfExists('compliance_requirements');
    }
};
