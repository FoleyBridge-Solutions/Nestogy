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
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('tax_category_id')->nullable();
            $table->string('profile_type');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('required_fields');
            $table->json('tax_types');
            $table->string('calculation_engine');
            $table->json('field_definitions')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('default_values')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
