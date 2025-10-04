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
        // Add code columns to usage tables
        if (Schema::hasTable('usage_pools')) {
            Schema::table('usage_pools', function (Blueprint $table) {
                $table->string('pool_code')->unique()->after('company_id');
            });
        }
        
        if (Schema::hasTable('usage_buckets')) {
            Schema::table('usage_buckets', function (Blueprint $table) {
                $table->string('bucket_code')->unique()->after('company_id');
            });
        }
        
        if (Schema::hasTable('usage_alerts')) {
            Schema::table('usage_alerts', function (Blueprint $table) {
                $table->string('alert_code')->unique()->after('company_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('usage_pools')) {
            Schema::table('usage_pools', function (Blueprint $table) {
                $table->dropColumn('pool_code');
            });
        }
        
        if (Schema::hasTable('usage_buckets')) {
            Schema::table('usage_buckets', function (Blueprint $table) {
                $table->dropColumn('bucket_code');
            });
        }
        
        if (Schema::hasTable('usage_alerts')) {
            Schema::table('usage_alerts', function (Blueprint $table) {
                $table->dropColumn('alert_code');
            });
        }
    }
};
