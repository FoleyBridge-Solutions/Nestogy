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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'tax_profile_id')) {
                $table->unsignedBigInteger('tax_profile_id')->nullable()->after('tax_rate');
                $table->index('tax_profile_id');
                $table->foreign('tax_profile_id')->references('id')->on('tax_profiles')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('products', 'tax_specific_data')) {
                $table->json('tax_specific_data')->nullable()->after('tax_profile_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['tax_profile_id']);
            $table->dropColumn(['tax_profile_id', 'tax_specific_data']);
        });
    }
};