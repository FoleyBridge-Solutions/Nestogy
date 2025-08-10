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
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code', 20)->unique();
            $table->string('color', 7)->default('#6B7280'); // Hex color
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->decimal('approval_limit', 10, 2)->default(0.00);
            $table->boolean('is_billable_default')->default(false);
            $table->decimal('markup_percentage_default', 5, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_active']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};