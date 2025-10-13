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
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->string('warranty_provider');
            $table->string('warranty_number')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('type', ['manufacturer', 'extended', 'service_contract'])->default('manufacturer');
            $table->text('coverage_details')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->enum('status', ['active', 'expired', 'claimed'])->default('active');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'asset_id']);
            $table->index(['company_id', 'end_date']);
            $table->index(['company_id', 'status']);
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
