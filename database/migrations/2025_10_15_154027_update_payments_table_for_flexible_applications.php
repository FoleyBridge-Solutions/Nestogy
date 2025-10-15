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
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
            
            $table->decimal('applied_amount', 10, 2)->default(0)->after('amount');
            $table->decimal('available_amount', 10, 2)->default(0)->after('applied_amount');
            $table->enum('application_status', ['unapplied', 'partially_applied', 'fully_applied'])->default('unapplied')->after('available_amount');
            $table->boolean('auto_apply')->default(true)->after('application_status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['applied_amount', 'available_amount', 'application_status', 'auto_apply']);
            
            $table->unsignedBigInteger('invoice_id')->nullable()->after('client_id');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }
};
