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
        // usage_pools - add cycle dates
        if (Schema::hasTable('usage_pools')) {
            Schema::table('usage_pools', function (Blueprint $table) {
                $table->date('cycle_start_date')->nullable()->after('pool_code');
                $table->date('cycle_end_date')->nullable()->after('cycle_start_date');
                $table->date('next_reset_date')->nullable()->after('cycle_end_date');
            });
        }
        
        // usage_buckets - add reset date
        if (Schema::hasTable('usage_buckets')) {
            Schema::table('usage_buckets', function (Blueprint $table) {
                $table->date('next_reset_date')->nullable()->after('bucket_code');
                $table->date('last_reset_date')->nullable()->after('next_reset_date');
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
                $table->dropColumn(['cycle_start_date', 'cycle_end_date', 'next_reset_date']);
            });
        }
        
        if (Schema::hasTable('usage_buckets')) {
            Schema::table('usage_buckets', function (Blueprint $table) {
                $table->dropColumn(['next_reset_date', 'last_reset_date']);
            });
        }
    }
};
