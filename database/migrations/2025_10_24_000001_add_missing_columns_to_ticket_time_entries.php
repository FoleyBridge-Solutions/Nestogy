<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_time_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('ticket_time_entries', 'hours_worked')) {
                $table->decimal('hours_worked', 8, 2)->nullable()->after('minutes');
            }
            if (!Schema::hasColumn('ticket_time_entries', 'minutes_worked')) {
                $table->integer('minutes_worked')->nullable()->after('hours_worked');
            }
            if (!Schema::hasColumn('ticket_time_entries', 'hours_billed')) {
                $table->decimal('hours_billed', 8, 2)->nullable()->after('minutes_worked');
            }
            if (!Schema::hasColumn('ticket_time_entries', 'work_performed')) {
                $table->text('work_performed')->nullable()->after('description');
            }
            if (!Schema::hasColumn('ticket_time_entries', 'work_date')) {
                $table->date('work_date')->nullable()->after('ended_at');
            }
            if (!Schema::hasColumn('ticket_time_entries', 'billable')) {
                $table->boolean('billable')->default(true)->after('work_date');
            }
            if (!Schema::hasColumn('ticket_time_entries', 'work_type')) {
                $table->string('work_type')->nullable()->after('entry_type');
            }
            if (!Schema::hasColumn('ticket_time_entries', 'rate_type')) {
                $table->string('rate_type')->nullable()->after('work_type');
            }
            if (!Schema::hasColumn('ticket_time_entries', 'status')) {
                $table->string('status')->default('submitted')->after('rate_type');
            }
            if (!Schema::hasColumn('ticket_time_entries', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('ticket_time_entries', 'submitted_by')) {
                $table->unsignedBigInteger('submitted_by')->nullable()->after('submitted_at');
            }
            if (!Schema::hasColumn('ticket_time_entries', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('submitted_by');
            }
            if (!Schema::hasColumn('ticket_time_entries', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('ticket_time_entries', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('ticket_time_entries', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
            if (!Schema::hasColumn('ticket_time_entries', 'amount')) {
                $table->decimal('amount', 10, 2)->nullable()->after('rejection_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ticket_time_entries', function (Blueprint $table) {
            $columns = ['hours_worked', 'minutes_worked', 'hours_billed', 'work_performed', 'work_date', 'work_type', 'rate_type', 'status', 'submitted_at', 'submitted_by', 'approved_at', 'approved_by', 'rejected_at', 'rejection_reason', 'amount'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('ticket_time_entries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
