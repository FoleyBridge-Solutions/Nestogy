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
        Schema::create('tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('tax_category_id')->nullable();
            $table->string('profile_type', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('required_fields');
            $table->json('tax_types');
            $table->string('calculation_engine', 100);
            $table->json('field_definitions')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('default_values')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(100);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('company_id');
            $table->index('category_id');
            $table->index('tax_category_id');
            $table->index('profile_type');
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'priority']);

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            // Comment out until tax_categories table exists
            // $table->foreign('tax_category_id')->references('id')->on('tax_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_profiles');
    }
};
