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
        Schema::create('jurisdiction_patterns_learned', function (Blueprint $table) {
            $table->id();
            $table->string('authority_name');
            $table->string('authority_id');
            $table->string('pattern_type')->default('discovered');
            $table->decimal('confidence', 3, 2)->default(0.50);
            $table->json('pattern_data')->nullable();
            $table->timestamp('discovered_at');
            $table->timestamps();

            // Indexes for fast lookups
            $table->index('authority_name');
            $table->index('authority_id');
            $table->index('pattern_type');
            $table->unique(['authority_name', 'authority_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurisdiction_patterns_learned');
    }
};
