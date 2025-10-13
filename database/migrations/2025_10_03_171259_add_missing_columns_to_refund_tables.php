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
        if (!Schema::hasTable('refund_requests')) {
            Schema::create('refund_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        
        if (!Schema::hasTable('refund_transactions')) {
            Schema::create('refund_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        
        if (Schema::hasTable('refund_requests')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('refund_requests', 'request_number')) {
                    $table->string('request_number')->nullable()->after('company_id');
                }
                if (!Schema::hasColumn('refund_requests', 'number')) {
                    $table->string('number')->nullable()->after('request_number');
                }
            });
        }
        
        if (Schema::hasTable('refund_transactions')) {
            Schema::table('refund_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('refund_transactions', 'processed_by')) {
                    $table->foreignId('processed_by')->nullable()->after('company_id')->constrained('users')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('refund_requests')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->dropColumn(['request_number', 'number']);
            });
        }
        
        if (Schema::hasTable('refund_transactions')) {
            Schema::table('refund_transactions', function (Blueprint $table) {
                $table->dropForeign(['processed_by']);
                $table->dropColumn(['processed_by']);
            });
        }
    }
};
