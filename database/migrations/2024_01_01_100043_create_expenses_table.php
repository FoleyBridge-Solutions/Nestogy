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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('category_id')->constrained('expense_categories')->onDelete('restrict');
                        $table->foreignId('user_id')->constrained()->onDelete('restrict');
                        $table->string('description');
                        $table->decimal('amount', 10, 2);
                        $table->date('expense_date');
                        $table->string('receipt_path')->nullable();
                        $table->text('notes')->nullable();
                        $table->boolean('is_billable')->default(false);
                        $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
                        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                        $table->timestamps();

                        $table->index(['company_id', 'expense_date']);
                        $table->index(['company_id', 'category_id']);
                        $table->index(['company_id', 'user_id']);
                        $table->index(['company_id', 'is_billable']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
