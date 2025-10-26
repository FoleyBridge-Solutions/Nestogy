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
        Schema::table('auto_payments', function (Blueprint $table) {
            $table->timestamp('activated_at')->nullable()->after('is_active');
            $table->timestamp('deactivated_at')->nullable()->after('activated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auto_payments', function (Blueprint $table) {
            $table->dropColumn(['activated_at', 'deactivated_at']);
        });
    }
};
