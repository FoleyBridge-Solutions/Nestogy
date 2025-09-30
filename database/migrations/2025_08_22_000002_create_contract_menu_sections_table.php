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
        Schema::create('contract_menu_sections', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();

            // Menu section configuration
            $table->string('section_slug')->index();
            $table->string('section_name');
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('contract_types')->nullable(); // Which contract types belong to this section
            $table->json('permissions')->nullable(); // Required permissions to see this section
            $table->json('config')->nullable(); // Additional configuration
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            // Indexes
            $table->index(['company_id', 'section_slug']);
            $table->index(['company_id', 'sort_order']);
            $table->index(['company_id', 'is_active']);

            // Unique constraint
            $table->unique(['company_id', 'section_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_menu_sections');
    }
};
