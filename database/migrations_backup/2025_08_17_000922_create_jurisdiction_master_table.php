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
        Schema::create('jurisdiction_master', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary()->autoIncrement();

            // Jurisdiction identification (optimized for performance)
            $table->string('jurisdiction_code', 20)->index();
            $table->string('jurisdiction_name', 100);
            $table->enum('jurisdiction_type', ['state', 'county', 'city', 'transit', 'special'])->index();
            $table->char('state_code', 2)->index();

            // Optional hierarchical relationships
            $table->unsignedInteger('parent_jurisdiction_id')->nullable();

            // Data source tracking for multi-state support
            $table->string('data_source', 30)->index();
            $table->timestamp('imported_at')->useCurrent();

            $table->timestamps();

            // Optimized indexes for fast tax rate lookups
            $table->unique(['state_code', 'jurisdiction_code'], 'uk_state_jurisdiction_code');
            $table->index(['jurisdiction_type', 'state_code'], 'idx_type_state');
            $table->index(['parent_jurisdiction_id'], 'idx_parent_jurisdiction');

        });

        // Add foreign key constraint after table creation (PostgreSQL compatibility)
        Schema::table('jurisdiction_master', function (Blueprint $table) {
            $table->foreign('parent_jurisdiction_id')->references('id')->on('jurisdiction_master')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurisdiction_master');
    }
};
