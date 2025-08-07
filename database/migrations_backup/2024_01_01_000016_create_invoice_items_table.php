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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 2)->default(0.00);
            $table->decimal('price', 15, 2)->default(0.00);
            $table->decimal('discount', 15, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax', 15, 2)->default(0.00);
            $table->decimal('total', 15, 2)->default(0.00);
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('tax_id')->nullable()->index();
            $table->unsignedBigInteger('quote_id')->nullable()->index();
            $table->unsignedBigInteger('recurring_id')->nullable()->index();
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('product_id')->nullable()->index();

            // Indexes
            $table->index('invoice_id');
            $table->index('order');
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};