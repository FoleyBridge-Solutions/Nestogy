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
        // Check existing columns to avoid conflicts
        $existingColumns = collect(Schema::getColumnListing('products'));

        Schema::table('products', function (Blueprint $table) use ($existingColumns) {
            // Only add columns that don't exist
            if (! $existingColumns->contains('sku')) {
                $table->string('sku')->nullable()->after('name');
            }
            if (! $existingColumns->contains('type')) {
                $table->enum('type', ['product', 'service'])->default('product')->after('description');
            }
            if (! $existingColumns->contains('subcategory_id')) {
                $table->unsignedBigInteger('subcategory_id')->nullable()->after('category_id');
            }

            // Tax and pricing
            if (! $existingColumns->contains('tax_inclusive')) {
                $table->boolean('tax_inclusive')->default(false)->after('currency_code');
            }
            if (! $existingColumns->contains('tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->nullable()->after('tax_inclusive');
            }

            // Product specifications
            if (! $existingColumns->contains('unit_type')) {
                $table->enum('unit_type', ['hours', 'units', 'days', 'weeks', 'months', 'years', 'fixed', 'subscription'])->default('units')->after('tax_rate');
            }
            if (! $existingColumns->contains('billing_model')) {
                $table->enum('billing_model', ['one_time', 'subscription', 'usage_based', 'hybrid'])->default('one_time')->after('unit_type');
            }
            if (! $existingColumns->contains('billing_cycle')) {
                $table->enum('billing_cycle', ['one_time', 'hourly', 'daily', 'weekly', 'monthly', 'quarterly', 'semi_annually', 'annually'])->default('one_time')->after('billing_model');
            }
            if (! $existingColumns->contains('billing_interval')) {
                $table->integer('billing_interval')->default(1)->after('billing_cycle');
            }

            // Inventory management
            if (! $existingColumns->contains('track_inventory')) {
                $table->boolean('track_inventory')->default(false)->after('billing_interval');
            }
            if (! $existingColumns->contains('current_stock')) {
                $table->integer('current_stock')->default(0)->after('track_inventory');
            }
            if (! $existingColumns->contains('reserved_stock')) {
                $table->integer('reserved_stock')->default(0)->after('current_stock');
            }
            if (! $existingColumns->contains('min_stock_level')) {
                $table->integer('min_stock_level')->default(0)->after('reserved_stock');
            }
            if (! $existingColumns->contains('max_quantity_per_order')) {
                $table->integer('max_quantity_per_order')->nullable()->after('min_stock_level');
            }
            if (! $existingColumns->contains('reorder_level')) {
                $table->integer('reorder_level')->nullable()->after('max_quantity_per_order');
            }

            // Product status and settings
            if (! $existingColumns->contains('is_active')) {
                $table->boolean('is_active')->default(true)->after('reorder_level');
            }
            if (! $existingColumns->contains('is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_active');
            }
            if (! $existingColumns->contains('is_taxable')) {
                $table->boolean('is_taxable')->default(true)->after('is_featured');
            }
            if (! $existingColumns->contains('allow_discounts')) {
                $table->boolean('allow_discounts')->default(true)->after('is_taxable');
            }
            if (! $existingColumns->contains('requires_approval')) {
                $table->boolean('requires_approval')->default(false)->after('allow_discounts');
            }

            // Advanced pricing
            if (! $existingColumns->contains('pricing_model')) {
                $table->enum('pricing_model', ['fixed', 'tiered', 'volume', 'usage', 'value', 'custom'])->default('fixed')->after('requires_approval');
            }
            if (! $existingColumns->contains('pricing_tiers')) {
                $table->json('pricing_tiers')->nullable()->after('pricing_model');
            }
            if (! $existingColumns->contains('discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->nullable()->after('pricing_tiers');
            }

            // Usage tracking for usage-based billing
            if (! $existingColumns->contains('usage_rate')) {
                $table->decimal('usage_rate', 10, 4)->nullable()->after('discount_percentage');
            }
            if (! $existingColumns->contains('usage_included')) {
                $table->integer('usage_included')->nullable()->after('usage_rate');
            }

            // Product metadata
            if (! $existingColumns->contains('features')) {
                $table->json('features')->nullable()->after('usage_included');
            }
            if (! $existingColumns->contains('tags')) {
                $table->json('tags')->nullable()->after('features');
            }
            if (! $existingColumns->contains('metadata')) {
                $table->json('metadata')->nullable()->after('tags');
            }
            if (! $existingColumns->contains('custom_fields')) {
                $table->json('custom_fields')->nullable()->after('metadata');
            }

            // Media
            if (! $existingColumns->contains('image_url')) {
                $table->string('image_url')->nullable()->after('custom_fields');
            }
            if (! $existingColumns->contains('gallery_urls')) {
                $table->json('gallery_urls')->nullable()->after('image_url');
            }

            // Analytics
            if (! $existingColumns->contains('sales_count')) {
                $table->integer('sales_count')->default(0)->after('gallery_urls');
            }
            if (! $existingColumns->contains('total_revenue')) {
                $table->decimal('total_revenue', 12, 2)->default(0)->after('sales_count');
            }
            if (! $existingColumns->contains('average_rating')) {
                $table->decimal('average_rating', 3, 2)->nullable()->after('total_revenue');
            }
            if (! $existingColumns->contains('rating_count')) {
                $table->integer('rating_count')->default(0)->after('average_rating');
            }

            // SEO and display
            if (! $existingColumns->contains('sort_order')) {
                $table->integer('sort_order')->default(0)->after('rating_count');
            }
            if (! $existingColumns->contains('short_description')) {
                $table->text('short_description')->nullable()->after('sort_order');
            }

            // Replace archived_at with proper soft deletes
            if (! $existingColumns->contains('deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Rename price to base_price if it exists
        if ($existingColumns->contains('price') && ! $existingColumns->contains('base_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->renameColumn('price', 'base_price');
            });
        }

        // Add indexes safely - PostgreSQL compatible
        $existingIndexes = collect(\DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'products'"))->pluck('indexname');

        Schema::table('products', function (Blueprint $table) use ($existingIndexes) {
            if (! $existingIndexes->contains('products_company_id_is_active_index')) {
                $table->index(['company_id', 'is_active']);
            }
            if (! $existingIndexes->contains('products_company_id_type_index')) {
                $table->index(['company_id', 'type']);
            }
            if (! $existingIndexes->contains('products_sku_index')) {
                $table->index('sku');
            }
            if (! $existingIndexes->contains('products_billing_model_index')) {
                $table->index('billing_model');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Remove all the added columns
            $table->dropColumn([
                'sku', 'type', 'subcategory_id', 'tax_inclusive', 'tax_rate',
                'unit_type', 'billing_model', 'billing_cycle', 'billing_interval',
                'track_inventory', 'current_stock', 'reserved_stock', 'min_stock_level',
                'max_quantity_per_order', 'reorder_level', 'is_active', 'is_featured',
                'is_taxable', 'allow_discounts', 'requires_approval', 'pricing_model',
                'pricing_tiers', 'discount_percentage', 'usage_rate', 'usage_included',
                'features', 'tags', 'metadata', 'custom_fields', 'image_url',
                'gallery_urls', 'sales_count', 'total_revenue', 'average_rating',
                'rating_count', 'sort_order', 'short_description',
            ]);

            // Restore archived_at
            $table->timestamp('archived_at')->nullable();
            $table->dropSoftDeletes();

            // Rename base_price back to price
            $table->renameColumn('base_price', 'price');
        });
    }
};
