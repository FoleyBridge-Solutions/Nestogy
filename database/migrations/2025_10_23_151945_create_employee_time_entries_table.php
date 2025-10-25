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
        Schema::create('employee_time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null');
            $table->foreignId('pay_period_id')->nullable()->constrained('pay_periods')->onDelete('set null');
            
            $table->dateTime('clock_in');
            $table->dateTime('clock_out')->nullable();
            $table->integer('total_minutes')->nullable();
            $table->integer('regular_minutes')->nullable();
            $table->integer('overtime_minutes')->nullable();
            $table->integer('double_time_minutes')->nullable()->default(0);
            $table->integer('break_minutes')->default(0);
            
            $table->enum('entry_type', ['clock', 'manual', 'imported', 'adjusted'])->default('clock');
            $table->enum('status', ['in_progress', 'completed', 'approved', 'rejected', 'paid'])->default('in_progress');
            
            $table->string('clock_in_ip')->nullable();
            $table->string('clock_out_ip')->nullable();
            $table->decimal('clock_in_latitude', 10, 7)->nullable();
            $table->decimal('clock_in_longitude', 10, 7)->nullable();
            $table->decimal('clock_out_latitude', 10, 7)->nullable();
            $table->decimal('clock_out_longitude', 10, 7)->nullable();
            
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->boolean('exported_to_payroll')->default(false);
            $table->timestamp('exported_at')->nullable();
            $table->string('payroll_batch_id')->nullable();
            
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'user_id', 'clock_in']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'pay_period_id']);
            $table->index(['user_id', 'clock_in', 'clock_out']);
            $table->index('exported_to_payroll');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_time_entries');
    }
};
