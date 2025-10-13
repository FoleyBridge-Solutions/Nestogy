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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('type'); // expense, income, ticket, product, invoice, quote, recurring, asset, expense_category, report, kb
            $table->string('code', 50)->nullable();
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            // Indexes
            $table->index('name');
            $table->index('type');
            $table->index('code');
            $table->index('slug');
            $table->index('parent_id');
            $table->index('company_id');
            $table->index('sort_order');
            $table->index('is_active');
            $table->index(['type', 'parent_id']);
            $table->index(['company_id', 'type']);
            $table->index(['type', 'is_active']);
            $table->index(['company_id', 'type', 'is_active']);
            $table->index('archived_at');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
