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
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('report_type')->nullable();
            $table->string('report_name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->string('frequency')->nullable();
            $table->string('schedule_config')->nullable();
            $table->string('filters')->nullable();
            $table->string('metrics')->nullable();
            $table->string('format')->nullable();
            $table->string('recipients')->nullable();
            $table->string('parameters')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamp('next_generation_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_size')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('auto_deliver')->nullable();
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
        Schema::dropIfExists('financial_reports');
    }
};
