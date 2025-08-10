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
        Schema::create('asset_warranties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('asset_id')->index();
            $table->date('warranty_start_date');
            $table->date('warranty_end_date');
            $table->string('warranty_provider');
            $table->enum('warranty_type', ['manufacturer', 'extended', 'third_party', 'service_contract']);
            $table->text('terms')->nullable();
            $table->text('coverage_details')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable()->index();
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('renewal_cost', 10, 2)->nullable();
            $table->boolean('auto_renewal')->default(false);
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending', 'suspended'])->default('active');
            $table->integer('claim_count')->default(0);
            $table->date('last_claim_date')->nullable();
            $table->boolean('renewal_reminder_sent')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');

            // Indexes for performance
            $table->index(['company_id', 'asset_id']);
            $table->index(['warranty_end_date', 'status']);
            $table->index(['warranty_type', 'status']);
            $table->index(['warranty_provider']);
            $table->index(['status', 'auto_renewal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_warranties');
    }
};
