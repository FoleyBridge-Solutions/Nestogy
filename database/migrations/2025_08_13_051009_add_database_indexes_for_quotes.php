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
        Schema::table('quotes', function (Blueprint $table) {
            // Check if indexes don't exist before creating them
            if (!$this->indexExists('quotes', 'quotes_company_status_idx')) {
                $table->index(['company_id', 'status'], 'quotes_company_status_idx');
            }
            if (!$this->indexExists('quotes', 'quotes_company_client_idx')) {
                $table->index(['company_id', 'client_id'], 'quotes_company_client_idx');
            }
            if (!$this->indexExists('quotes', 'quotes_company_created_idx')) {
                $table->index(['company_id', 'created_at'], 'quotes_company_created_idx');
            }
            if (!$this->indexExists('quotes', 'quotes_company_date_idx')) {
                $table->index(['company_id', 'date'], 'quotes_company_date_idx');
            }
            if (!$this->indexExists('quotes', 'quotes_company_expire_idx')) {
                $table->index(['company_id', 'expire'], 'quotes_company_expire_idx');
            }
            
            // Individual indexes for filtering and sorting
            if (!$this->indexExists('quotes', 'quotes_number_idx')) {
                $table->index('number', 'quotes_number_idx');
            }
            if (!$this->indexExists('quotes', 'quotes_status_idx')) {
                $table->index('status', 'quotes_status_idx');
            }
            if (!$this->indexExists('quotes', 'quotes_total_idx')) {
                $table->index('amount', 'quotes_total_idx');
            }
            if (!$this->indexExists('quotes', 'quotes_status_expire_idx')) {
                $table->index(['status', 'expire'], 'quotes_status_expire_idx');
            }
            
            // Note: Fulltext index on number column is not possible since it's an integer
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            // Composite indexes for quote items (stored in invoice_items table)
            if (!$this->indexExists('invoice_items', 'invoice_items_quote_order_idx')) {
                $table->index(['quote_id', 'order'], 'invoice_items_quote_order_idx');
            }
            // Index on product_id only (service_id column doesn't exist)
            if (!$this->indexExists('invoice_items', 'invoice_items_product_idx')) {
                $table->index('product_id', 'invoice_items_product_idx');
            }
            
            // Individual indexes
            if (!$this->indexExists('invoice_items', 'invoice_items_price_idx')) {
                $table->index('price', 'invoice_items_price_idx');
            }
            if (!$this->indexExists('invoice_items', 'invoice_items_quantity_idx')) {
                $table->index('quantity', 'invoice_items_quantity_idx');
            }
            if (!$this->indexExists('invoice_items', 'invoice_items_subtotal_idx')) {
                $table->index('subtotal', 'invoice_items_subtotal_idx');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            // Client indexes for quote filtering
            if (!$this->indexExists('clients', 'clients_company_name_idx')) {
                $table->index(['company_id', 'name'], 'clients_company_name_idx');
            }
            if (!$this->indexExists('clients', 'clients_company_status_idx')) {
                $table->index(['company_id', 'status'], 'clients_company_status_idx');
            }
            
            // Full-text search for client names and company names
            if (config('database.default') === 'mysql' && !$this->indexExists('clients', 'clients_name_fulltext')) {
                $table->fulltext(['name', 'company_name'], 'clients_name_fulltext');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            // Category indexes (no status column exists)
            if (!$this->indexExists('categories', 'categories_type_idx')) {
                $table->index('type', 'categories_type_idx');
            }
            if (!$this->indexExists('categories', 'categories_name_idx')) {
                $table->index('name', 'categories_name_idx');
            }
        });

        Schema::table('quote_templates', function (Blueprint $table) {
            // Template indexes
            if (!$this->indexExists('quote_templates', 'templates_company_active_idx')) {
                $table->index(['company_id', 'is_active'], 'templates_company_active_idx');
            }
            if (!$this->indexExists('quote_templates', 'templates_company_category_idx')) {
                $table->index(['company_id', 'category'], 'templates_company_category_idx');
            }
            // Skip usage_count and last_used_at as they don't exist
            
            // Full-text search for template names and descriptions
            if (config('database.default') === 'mysql' && !$this->indexExists('quote_templates', 'templates_content_fulltext')) {
                $table->fulltext(['name', 'description'], 'templates_content_fulltext');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            // Product indexes for catalog performance (no status column)
            if (!$this->indexExists('products', 'products_company_category_idx')) {
                $table->index(['company_id', 'category_id'], 'products_company_category_idx');
            }
            if (!$this->indexExists('products', 'products_company_featured_idx')) {
                $table->index(['company_id', 'is_featured'], 'products_company_featured_idx');
            }
            if (!$this->indexExists('products', 'products_price_idx')) {
                $table->index('base_price', 'products_price_idx');
            }
            if (!$this->indexExists('products', 'products_sku_idx')) {
                $table->index('sku', 'products_sku_idx');
            }
            
            // Full-text search for product search
            if (config('database.default') === 'mysql' && !$this->indexExists('products', 'products_search_fulltext')) {
                $table->fulltext(['name', 'description', 'sku'], 'products_search_fulltext');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            // Service indexes - simple indexes based on actual columns
            if (!$this->indexExists('services', 'services_product_idx')) {
                $table->index('product_id', 'services_product_idx');
            }
            if (!$this->indexExists('services', 'services_type_idx')) {
                $table->index('service_type', 'services_type_idx');
            }
            // Skip fulltext as there's no name/description columns
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        if ($driver === 'pgsql') {
            // PostgreSQL query
            $result = $connection->select(
                "SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$table, $index]
            );
        } else {
            // MySQL query
            $databaseName = $connection->getDatabaseName();
            $result = $connection->select(
                "SELECT 1 FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$databaseName, $table, $index]
            );
        }
        
        return !empty($result);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex('quotes_company_status_idx');
            $table->dropIndex('quotes_company_client_idx');
            $table->dropIndex('quotes_company_created_idx');
            $table->dropIndex('quotes_company_date_idx');
            $table->dropIndex('quotes_company_expire_idx');
            $table->dropIndex('quotes_number_idx');
            $table->dropIndex('quotes_status_idx');
            $table->dropIndex('quotes_total_idx');
            $table->dropIndex('quotes_status_expire_idx');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex('invoice_items_quote_order_idx');
            $table->dropIndex('invoice_items_product_idx');
            $table->dropIndex('invoice_items_price_idx');
            $table->dropIndex('invoice_items_quantity_idx');
            $table->dropIndex('invoice_items_subtotal_idx');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_company_name_idx');
            $table->dropIndex('clients_company_status_idx');
            
            if (config('database.default') === 'mysql') {
                $table->dropIndex('clients_name_fulltext');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_type_idx');
            $table->dropIndex('categories_name_idx');
        });

        Schema::table('quote_templates', function (Blueprint $table) {
            $table->dropIndex('templates_company_active_idx');
            $table->dropIndex('templates_company_category_idx');
            
            if (config('database.default') === 'mysql') {
                $table->dropIndex('templates_content_fulltext');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_company_category_idx');
            $table->dropIndex('products_company_featured_idx');
            $table->dropIndex('products_price_idx');
            $table->dropIndex('products_sku_idx');
            
            if (config('database.default') === 'mysql') {
                $table->dropIndex('products_search_fulltext');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('services_product_idx');
            $table->dropIndex('services_type_idx');
        });
    }
};