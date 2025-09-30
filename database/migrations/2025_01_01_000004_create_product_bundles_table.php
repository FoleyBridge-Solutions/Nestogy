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
        // Bundle definitions
        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->nullable();
            $table->enum('bundle_type', ['fixed', 'configurable', 'dynamic'])->default('fixed');

            // Pricing
            $table->enum('pricing_type', ['sum', 'fixed', 'percentage_discount'])->default('sum');
            $table->decimal('fixed_price', 10, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('min_value', 10, 2)->nullable(); // Minimum bundle value for discount

            // Availability
            $table->boolean('is_active')->default(true);
            $table->dateTime('available_from')->nullable();
            $table->dateTime('available_until')->nullable();
            $table->integer('max_quantity')->nullable();

            // Display
            $table->string('image_url')->nullable();
            $table->boolean('show_items_separately')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        // Bundle items
        Schema::create('product_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained('product_bundles')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_default')->default(true); // For configurable bundles

            // Item-specific pricing
            $table->enum('discount_type', ['percentage', 'fixed', 'none'])->default('none');
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->decimal('price_override', 10, 2)->nullable();

            // Configurable options
            $table->integer('min_quantity')->default(0);
            $table->integer('max_quantity')->nullable();
            $table->json('allowed_variants')->nullable(); // For product variants

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['bundle_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_bundle_items');
        Schema::dropIfExists('product_bundles');
    }
};
