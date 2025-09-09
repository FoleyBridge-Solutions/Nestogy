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
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('sla_id')->nullable()->after('status');
            
            $table->foreign('sla_id')->references('id')->on('slas')->onDelete('set null');
            $table->index(['company_id', 'sla_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['sla_id']);
            $table->dropIndex(['company_id', 'sla_id']);
            $table->dropColumn('sla_id');
        });
    }
};
