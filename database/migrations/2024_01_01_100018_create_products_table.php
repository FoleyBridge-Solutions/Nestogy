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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->enum('type', ['product', 'service'])->default('product');
            $table->decimal('base_price', 15, 2);
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('currency_code', 3);
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('subcategory_id')->nullable();

            // Tax and pricing
            $table->boolean('tax_inclusive')->default(false);
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->unsignedBigInteger('tax_id')->nullable();
            $table->unsignedBigInteger('tax_profile_id')->nullable();

            // Product specifications
            $table->enum('unit_type', ['hours', 'units', 'days', 'weeks', 'months', 'years', 'fixed', 'subscription'])->default('units');
            $table->enum('billing_model', ['one_time', 'subscription', 'usage_based', 'hybrid'])->default('one_time');
            $table->enum('billing_cycle', ['one_time', 'hourly', 'daily', 'weekly', 'monthly', 'quarterly', 'semi_annually', 'annually'])->default('one_time');
            $table->integer('billing_interval')->default(1);

            // Inventory management
            $table->boolean('track_inventory')->default(false);
            $table->integer('current_stock')->default(0);
            $table->integer('reserved_stock')->default(0);
            $table->integer('min_stock_level')->default(0);
            $table->integer('max_quantity_per_order')->nullable();
            $table->integer('reorder_level')->nullable();

            // Product status and settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('allow_discounts')->default(true);
            $table->boolean('requires_approval')->default(false);

            // Advanced pricing
            $table->enum('pricing_model', ['fixed', 'tiered', 'volume', 'usage', 'value', 'custom'])->default('fixed');
            $table->json('pricing_tiers')->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();

            // Usage tracking for usage-based billing
            $table->decimal('usage_rate', 10, 4)->nullable();
            $table->integer('usage_included')->nullable();

            // Product metadata
            $table->json('features')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->json('custom_fields')->nullable();

            // Media
            $table->string('image_url')->nullable();
            $table->json('gallery_urls')->nullable();

            // Analytics
            $table->integer('sales_count')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->integer('rating_count')->default(0);

            // SEO and display
            $table->integer('sort_order')->default(0);

            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('name');
            $table->index('sku');
            $table->index('category_id');
            $table->index('company_id');
            $table->index('tax_profile_id');
            $table->index('base_price');
            $table->index('type');
            $table->index('billing_model');
            $table->index(['company_id', 'category_id']);
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'type']);

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
