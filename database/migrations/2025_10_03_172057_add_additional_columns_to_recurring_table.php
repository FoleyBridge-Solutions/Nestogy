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
        if (!Schema::hasTable('recurring')) {
            Schema::create('recurring', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        
        Schema::table('recurring', function (Blueprint $table) {
            if (!Schema::hasColumn('recurring', 'auto_invoice_generation')) {
                $table->boolean('auto_invoice_generation')->default(true)->after('overage_rates');
            }
            if (!Schema::hasColumn('recurring', 'email_template')) {
                $table->string('email_template')->nullable();
            }
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
