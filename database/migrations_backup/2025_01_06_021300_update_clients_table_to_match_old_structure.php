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
        Schema::table('clients', function (Blueprint $table) {
            // Add missing fields from old structure
            $table->boolean('lead')->default(false)->after('company_id');
            $table->string('type')->nullable()->after('company_name');
            $table->string('referral')->nullable()->after('website');
            $table->decimal('rate', 15, 2)->nullable()->after('referral');
            $table->string('currency_code', 3)->default('USD')->after('rate');
            $table->integer('net_terms')->default(30)->after('currency_code');
            $table->string('tax_id_number')->nullable()->after('net_terms');
            $table->integer('rmm_id')->nullable()->after('tax_id_number');
            $table->timestamp('accessed_at')->nullable()->after('deleted_at');
            $table->unsignedBigInteger('created_by')->nullable()->after('accessed_at');
            
            // Add indexes
            $table->index('lead');
            $table->index('type');
            $table->index('accessed_at');
            $table->index(['company_id', 'lead']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Remove indexes
            $table->dropIndex(['company_id', 'lead']);
            $table->dropIndex(['accessed_at']);
            $table->dropIndex(['type']);
            $table->dropIndex(['lead']);
            
            // Remove columns
            $table->dropColumn([
                'lead',
                'type',
                'referral',
                'rate',
                'currency_code',
                'net_terms',
                'tax_id_number',
                'rmm_id',
                'accessed_at',
                'created_by'
            ]);
        });
    }
};