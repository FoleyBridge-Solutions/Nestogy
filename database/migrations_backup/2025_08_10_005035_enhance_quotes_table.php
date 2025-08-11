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
            // Add company_id if it doesn't exist (for company scoping)
            if (!Schema::hasColumn('quotes', 'company_id')) {
                $table->unsignedBigInteger('company_id')->after('id')->index();
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }

            // Enterprise workflow fields
            $table->integer('version')->default(1)->after('number');
            $table->enum('approval_status', [
                'pending', 
                'manager_approved', 
                'executive_approved', 
                'rejected', 
                'not_required'
            ])->default('pending')->after('status');

            // Enhanced date fields
            $table->date('valid_until')->nullable()->after('expire');
            
            // Enhanced discount fields
            $table->enum('discount_type', ['percentage', 'fixed'])->default('fixed')->after('discount_amount');

            // Enterprise content fields
            $table->text('terms_conditions')->nullable()->after('note');

            // Auto-renewal features
            $table->boolean('auto_renew')->default(false)->after('url_key');
            $table->integer('auto_renew_days')->nullable()->after('auto_renew');

            // Template and VoIP configuration
            $table->string('template_name', 100)->nullable()->after('auto_renew_days');
            $table->json('voip_config')->nullable()->after('template_name');
            $table->json('pricing_model')->nullable()->after('voip_config');

            // Tracking timestamps
            $table->timestamp('sent_at')->nullable()->after('pricing_model');
            $table->timestamp('viewed_at')->nullable()->after('sent_at');
            $table->timestamp('accepted_at')->nullable()->after('viewed_at');
            $table->timestamp('declined_at')->nullable()->after('accepted_at');

            // Relationship fields
            $table->unsignedBigInteger('parent_quote_id')->nullable()->after('client_id');
            $table->unsignedBigInteger('converted_invoice_id')->nullable()->after('parent_quote_id');
            $table->unsignedBigInteger('created_by')->nullable()->after('converted_invoice_id');
            $table->unsignedBigInteger('approved_by')->nullable()->after('created_by');

            // Add foreign key constraints
            $table->foreign('parent_quote_id')->references('id')->on('quotes')->onDelete('set null');
            $table->foreign('converted_invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Add indexes for performance (check if they don't exist first)
            $indexesToAdd = [
                'quotes_company_id_status_index' => ['company_id', 'status'],
                'quotes_company_id_approval_status_index' => ['company_id', 'approval_status'],
                'quotes_company_id_client_id_index' => ['company_id', 'client_id'],
                'quotes_expire_status_index' => ['expire', 'status'],
                'quotes_valid_until_status_index' => ['valid_until', 'status'],
                'quotes_template_name_index' => 'template_name'
            ];

            foreach ($indexesToAdd as $indexName => $columns) {
                // Check if index doesn't already exist
                try {
                    $exists = collect(Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('quotes'))
                        ->has($indexName);
                    
                    if (!$exists) {
                        if (is_array($columns)) {
                            $table->index($columns);
                        } else {
                            $table->index($columns);
                        }
                    }
                } catch (\Exception $e) {
                    // Skip if we can't check for existing indexes
                    continue;
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['parent_quote_id']);
            $table->dropForeign(['converted_invoice_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['approved_by']);
            
            if (Schema::hasColumn('quotes', 'company_id')) {
                $table->dropForeign(['company_id']);
            }

            // Drop indexes
            $table->dropIndex(['company_id', 'status']);
            $table->dropIndex(['company_id', 'approval_status']);
            $table->dropIndex(['company_id', 'client_id']);
            $table->dropIndex(['expire', 'status']);
            $table->dropIndex(['valid_until', 'status']);
            $table->dropIndex(['template_name']);

            // Drop columns
            $table->dropColumn([
                'version',
                'approval_status',
                'valid_until',
                'discount_type',
                'terms_conditions',
                'auto_renew',
                'auto_renew_days',
                'template_name',
                'voip_config',
                'pricing_model',
                'sent_at',
                'viewed_at',
                'accepted_at',
                'declined_at',
                'parent_quote_id',
                'converted_invoice_id',
                'created_by',
                'approved_by'
            ]);

            if (Schema::hasColumn('quotes', 'company_id')) {
                $table->dropColumn('company_id');
            }
        });
    }
};
