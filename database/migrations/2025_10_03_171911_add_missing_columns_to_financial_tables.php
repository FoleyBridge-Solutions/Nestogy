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
        if (Schema::hasTable('payment_plans')) {
            Schema::table('payment_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('payment_plans', 'plan_number')) {
                    $table->string('plan_number')->unique()->after('company_id');
                }
            });
        }
        
        if (Schema::hasTable('credit_note_items')) {
            Schema::table('credit_note_items', function (Blueprint $table) {
                if (!Schema::hasColumn('credit_note_items', 'line_total')) {
                    $table->decimal('line_total', 15, 2)->default(0)->after('company_id');
                }
            });
        }
        
        if (Schema::hasTable('credit_note_approvals')) {
            Schema::table('credit_note_approvals', function (Blueprint $table) {
                if (!Schema::hasColumn('credit_note_approvals', 'sla_hours')) {
                    $table->integer('sla_hours')->nullable()->after('expired_at');
                }
                if (!Schema::hasColumn('credit_note_approvals', 'sla_deadline')) {
                    $table->timestamp('sla_deadline')->nullable()->after('sla_hours');
                }
            });
        }
        
        if (Schema::hasTable('refund_requests')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('refund_requests', 'name')) {
                    $table->string('name')->nullable()->after('number');
                }
            });
        }
        
        if (Schema::hasTable('refund_transactions')) {
            Schema::table('refund_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('refund_transactions', 'transaction_id')) {
                    $table->string('transaction_id')->unique()->after('processed_by');
                }
            });
        }
        
        if (Schema::hasTable('quote_invoice_conversions')) {
            Schema::table('quote_invoice_conversions', function (Blueprint $table) {
                if (!Schema::hasColumn('quote_invoice_conversions', 'status')) {
                    $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending')->after('company_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payment_plans')) {
            Schema::table('payment_plans', function (Blueprint $table) {
                $table->dropColumn('plan_number');
            });
        }
        
        if (Schema::hasTable('credit_note_items')) {
            Schema::table('credit_note_items', function (Blueprint $table) {
                $table->dropColumn('line_total');
            });
        }
        
        if (Schema::hasTable('credit_note_approvals')) {
            Schema::table('credit_note_approvals', function (Blueprint $table) {
                $table->dropColumn(['sla_hours', 'sla_deadline']);
            });
        }
        
        if (Schema::hasTable('refund_requests')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
        
        if (Schema::hasTable('refund_transactions')) {
            Schema::table('refund_transactions', function (Blueprint $table) {
                $table->dropColumn('transaction_id');
            });
        }
        
        if (Schema::hasTable('quote_invoice_conversions')) {
            Schema::table('quote_invoice_conversions', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
