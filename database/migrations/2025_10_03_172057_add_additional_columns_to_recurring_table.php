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
        Schema::table('recurring', function (Blueprint $table) {
            $table->boolean('auto_invoice_generation')->default(true)->after('overage_rates');
            $table->string('email_template')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recurring', function (Blueprint $table) {
            $table->dropColumn(['auto_invoice_generation', 'email_template']);
        });
    }
};
