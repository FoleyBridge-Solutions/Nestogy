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
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('asset_id')->index();
            $table->decimal('original_cost', 12, 2);
            $table->decimal('salvage_value', 12, 2)->nullable();
            $table->integer('useful_life_years');
            $table->enum('method', ['straight_line', 'declining_balance', 'double_declining', 'sum_of_years', 'units_of_production']);
            $table->decimal('depreciation_rate', 5, 4)->nullable(); // For declining balance method
            $table->date('start_date');
            $table->decimal('annual_depreciation', 12, 2)->default(0);
            $table->decimal('accumulated_depreciation', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->integer('units_produced')->nullable(); // For units of production method
            $table->integer('total_units_expected')->nullable(); // For units of production method
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');

            // Indexes for performance
            $table->index(['company_id', 'asset_id']);
            $table->index(['method', 'start_date']);
            $table->unique(['asset_id']); // One depreciation record per asset
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_depreciations');
    }
};
