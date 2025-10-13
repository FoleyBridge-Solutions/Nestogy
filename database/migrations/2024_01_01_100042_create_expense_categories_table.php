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
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->string('name');
                        $table->text('description')->nullable();
                        $table->string('color', 7)->nullable();
                        $table->boolean('is_active')->default(true);
                        $table->timestamps();
                        $table->json('metadata')->nullable();

                        $table->index(['company_id', 'is_active']);
                        $table->unique(['company_id', 'name']);
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
