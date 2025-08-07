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
        Schema::table('invoice_items', function (Blueprint $table) {
            // Add foreign key constraints for tables that will exist later
            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('set null');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('cascade');
            $table->foreign('recurring_id')->references('id')->on('recurring')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['tax_id']);
            $table->dropForeign(['quote_id']);
            $table->dropForeign(['recurring_id']);
            $table->dropForeign(['product_id']);
        });
    }
};