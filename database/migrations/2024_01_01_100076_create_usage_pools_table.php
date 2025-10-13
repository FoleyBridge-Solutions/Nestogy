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
        Schema::create('usage_pools', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->string('name');
                        $table->timestamps();
                        $table->date('cycle_start_date')->nullable();
                        $table->date('cycle_end_date')->nullable();
                        $table->date('next_reset_date')->nullable();
                        $table->softDeletes();

                        // Indexes from ALTER migrations
                        $table->string('pool_code')->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_pools');
    }
};
