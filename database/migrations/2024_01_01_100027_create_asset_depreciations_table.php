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
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->decimal('purchase_cost', 12, 2);
            $table->decimal('residual_value', 12, 2)->default(0);
            $table->integer('useful_life_years');
            $table->enum('method', ['straight_line', 'declining_balance', 'sum_of_years'])->default('straight_line');
            $table->decimal('annual_depreciation', 12, 2);
            $table->decimal('accumulated_depreciation', 12, 2)->default(0);
            $table->decimal('current_book_value', 12, 2);
            $table->date('depreciation_start_date');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'asset_id']);
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
