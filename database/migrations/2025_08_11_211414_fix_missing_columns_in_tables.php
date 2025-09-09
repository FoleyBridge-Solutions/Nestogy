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
        // Add missing asset_tag column to assets table
        Schema::table('assets', function (Blueprint $table) {
            $table->string('asset_tag')->nullable()->after('name');
        });

        // Add missing columns to ticket_time_entries table
        Schema::table('ticket_time_entries', function (Blueprint $table) {
            // Add work_date column
            $table->date('work_date')->nullable()->after('user_id');
            
            // Add hours column (for compatibility with dashboard)
            $table->decimal('hours', 8, 2)->nullable()->after('minutes');
            
            // Add additional columns expected by the model
            $table->text('work_performed')->nullable()->after('description');
            $table->decimal('hours_worked', 8, 2)->nullable()->after('hours');
            $table->integer('minutes_worked')->nullable()->after('hours_worked');
            $table->decimal('hours_billed', 8, 2)->nullable()->after('minutes_worked');
            $table->boolean('billable')->default(true)->after('hours_billed');
            $table->decimal('amount', 10, 2)->nullable()->after('hourly_rate');
            $table->string('entry_type')->default('manual')->after('amount');
            $table->string('work_type')->nullable()->after('entry_type');
            $table->string('rate_type')->nullable()->after('work_type');
            $table->string('status')->nullable()->after('rate_type');
            
            // Approval workflow columns
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->unsignedBigInteger('submitted_by')->nullable()->after('submitted_at');
            $table->timestamp('approved_at')->nullable()->after('submitted_by');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            $table->text('approval_notes')->nullable()->after('approved_by');
            $table->timestamp('rejected_at')->nullable()->after('approval_notes');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            $table->text('rejection_reason')->nullable()->after('rejected_by');
            
            // Invoice tracking
            $table->unsignedBigInteger('invoice_id')->nullable()->after('rejection_reason');
            $table->timestamp('invoiced_at')->nullable()->after('invoice_id');
            
            // Metadata
            $table->json('metadata')->nullable()->after('invoiced_at');
            
            // Add soft deletes
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('asset_tag');
        });

        Schema::table('ticket_time_entries', function (Blueprint $table) {
            $table->dropColumn([
                'work_date',
                'hours',
                'work_performed',
                'hours_worked',
                'minutes_worked',
                'hours_billed',
                'billable',
                'amount',
                'entry_type',
                'work_type',
                'rate_type',
                'status',
                'submitted_at',
                'submitted_by',
                'approved_at',
                'approved_by',
                'approval_notes',
                'rejected_at',
                'rejected_by',
                'rejection_reason',
                'invoice_id',
                'invoiced_at',
                'metadata',
                'deleted_at'
            ]);
        });
    }
};