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
        if (!Schema::hasTable('usage_pools')) {
            Schema::create('usage_pools', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        
        if (!Schema::hasTable('usage_buckets')) {
            Schema::create('usage_buckets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        
        if (!Schema::hasTable('usage_alerts')) {
            Schema::create('usage_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        
        if (Schema::hasTable('usage_pools')) {
            Schema::table('usage_pools', function (Blueprint $table) {
                if (!Schema::hasColumn('usage_pools', 'pool_code')) {
                    $table->string('pool_code')->unique()->after('company_id');
                }
            });
        }
        
        if (Schema::hasTable('usage_buckets')) {
            Schema::table('usage_buckets', function (Blueprint $table) {
                if (!Schema::hasColumn('usage_buckets', 'bucket_code')) {
                    $table->string('bucket_code')->unique()->after('company_id');
                }
            });
        }
        
        if (Schema::hasTable('usage_alerts')) {
            Schema::table('usage_alerts', function (Blueprint $table) {
                if (!Schema::hasColumn('usage_alerts', 'alert_code')) {
                    $table->string('alert_code')->unique()->after('company_id');
                }
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
