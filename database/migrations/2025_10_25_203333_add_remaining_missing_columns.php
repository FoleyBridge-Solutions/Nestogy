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
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('company_id')->constrained()->onDelete('cascade');
        });
        
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('company_id')->constrained()->onDelete('cascade');
        });
        
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('company_id')->constrained()->onDelete('cascade');
        });
        
        Schema::table('auto_payments', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('client_id')->constrained()->onDelete('set null');
        });
        
        Schema::table('dunning_campaigns', function (Blueprint $table) {
            $table->string('status')->default('active')->after('name');
        });
        
        Schema::table('usage_buckets', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('company_id')->constrained()->onDelete('cascade');
        });
        
        Schema::table('usage_alerts', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('company_id')->constrained()->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
        
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
        
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
        
        Schema::table('auto_payments', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });
        
        Schema::table('dunning_campaigns', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('usage_buckets', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
        
        Schema::table('usage_alerts', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};
