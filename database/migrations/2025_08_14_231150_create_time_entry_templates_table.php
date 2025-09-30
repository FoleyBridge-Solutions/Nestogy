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
        Schema::create('time_entry_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('work_type');
            $table->decimal('default_hours', 5, 2);
            $table->string('category')->nullable();
            $table->json('keywords')->nullable(); // Keywords for auto-suggestion
            $table->boolean('is_active')->default(true);
            $table->boolean('is_billable')->default(true);
            $table->integer('usage_count')->default(0);
            $table->json('metadata')->nullable(); // Additional settings
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index(['work_type', 'category']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entry_templates');
    }
};
