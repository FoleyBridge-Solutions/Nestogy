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
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false)->after('status');
            $table->foreignId('recurring_invoice_id')->nullable()->after('is_recurring')
                ->constrained('recurring')->onDelete('set null');
            $table->string('recurring_frequency')->nullable()->after('recurring_invoice_id')
                ->comment('monthly, quarterly, yearly, etc.');
            $table->date('next_recurring_date')->nullable()->after('recurring_frequency');
            
            $table->index('is_recurring');
            $table->index('next_recurring_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['is_recurring']);
            $table->dropIndex(['next_recurring_date']);
            $table->dropForeign(['recurring_invoice_id']);
            $table->dropColumn([
                'is_recurring',
                'recurring_invoice_id',
                'recurring_frequency',
                'next_recurring_date'
            ]);
        });
    }
};
