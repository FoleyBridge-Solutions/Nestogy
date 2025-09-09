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
        Schema::table('companies', function (Blueprint $table) {
            // Hierarchy structure fields
            $table->unsignedBigInteger('parent_company_id')->nullable()->after('id');
            $table->enum('company_type', ['root', 'subsidiary', 'division'])->default('root')->after('parent_company_id');
            $table->unsignedInteger('organizational_level')->default(0)->after('company_type');
            $table->json('subsidiary_settings')->nullable()->after('organizational_level');
            
            // Access control fields
            $table->enum('access_level', ['full', 'limited', 'read_only'])->default('full')->after('subsidiary_settings');
            
            // Billing relationship fields
            $table->enum('billing_type', ['independent', 'parent_billed', 'shared'])->default('independent')->after('access_level');
            $table->unsignedBigInteger('billing_parent_id')->nullable()->after('billing_type');
            
            // Hierarchy management fields
            $table->boolean('can_create_subsidiaries')->default(false)->after('billing_parent_id');
            $table->unsignedInteger('max_subsidiary_depth')->default(3)->after('can_create_subsidiaries');
            $table->json('inherited_permissions')->nullable()->after('max_subsidiary_depth');
            
            // Add foreign key constraints
            $table->foreign('parent_company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('set null');
                
            $table->foreign('billing_parent_id')
                ->references('id')
                ->on('companies')
                ->onDelete('set null');
            
            // Add indexes for performance
            $table->index(['parent_company_id', 'company_type'], 'companies_hierarchy_idx');
            $table->index(['organizational_level'], 'companies_level_idx');
            $table->index(['billing_parent_id'], 'companies_billing_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['parent_company_id']);
            $table->dropForeign(['billing_parent_id']);
            
            // Drop indexes
            $table->dropIndex('companies_hierarchy_idx');
            $table->dropIndex('companies_level_idx');
            $table->dropIndex('companies_billing_idx');
            
            // Drop columns
            $table->dropColumn([
                'parent_company_id',
                'company_type',
                'organizational_level',
                'subsidiary_settings',
                'access_level',
                'billing_type',
                'billing_parent_id',
                'can_create_subsidiaries',
                'max_subsidiary_depth',
                'inherited_permissions'
            ]);
        });
    }
};