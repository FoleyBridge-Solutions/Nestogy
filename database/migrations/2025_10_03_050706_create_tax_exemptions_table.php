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
        Schema::create('tax_exemptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('tax_jurisdiction_id')->nullable();
            $table->unsignedBigInteger('tax_category_id')->nullable();
            $table->string('exemption_type')->nullable();
            $table->string('exemption_name');
            $table->string('certificate_number')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->string('issuing_state')->nullable();
            $table->timestamp('issue_date')->nullable();
            $table->timestamp('expiry_date')->nullable();
            $table->boolean('is_blanket_exemption')->default(false);
            $table->string('applicable_tax_types')->nullable();
            $table->string('applicable_services')->nullable();
            $table->string('exemption_conditions')->nullable();
            $table->string('exemption_percentage')->nullable();
            $table->decimal('maximum_exemption_amount', 15, 2)->default(0);
            $table->string('status')->default('active');
            $table->string('verification_status')->default('active');
            $table->timestamp('last_verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->string('certificate_file_path')->nullable();
            $table->string('supporting_documents')->nullable();
            $table->string('auto_apply')->nullable();
            $table->string('priority')->nullable();
            $table->string('metadata')->nullable();
            $table->string('created_by')->nullable();
            $table->string('verified_by')->nullable();
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
        Schema::dropIfExists('tax_exemptions');
    }
};
