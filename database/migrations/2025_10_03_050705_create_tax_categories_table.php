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
        Schema::create('tax_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('category_type')->nullable();
            $table->text('description')->nullable();
            $table->string('service_types')->nullable();
            $table->string('tax_rules')->nullable();
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_interstate')->default(false);
            $table->boolean('is_international')->default(false);
            $table->string('requires_jurisdiction_detection')->nullable();
            $table->string('default_tax_treatment')->nullable();
            $table->string('exemption_rules')->nullable();
            $table->string('priority')->nullable();
            $table->boolean('is_active')->default(false);
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
        Schema::dropIfExists('tax_categories');
    }
};
