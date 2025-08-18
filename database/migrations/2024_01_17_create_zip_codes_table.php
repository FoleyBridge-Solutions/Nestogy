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
        if (!Schema::hasTable('zip_codes')) {
            Schema::create('zip_codes', function (Blueprint $table) {
                $table->id();
                $table->string('zip_code', 5);
                $table->string('city');
                $table->string('state_code', 2);
                $table->string('county_name')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['zip_code', 'state_code']);
                $table->index('city');
                $table->index('county_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zip_codes');
    }
};