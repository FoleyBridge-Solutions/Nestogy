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
        Schema::create('recurring', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('prefix')->nullable();
            $table->integer('number');
            $table->string('scope')->nullable();
            $table->string('frequency'); // Monthly, Quarterly, Yearly, etc.
            $table->date('last_sent')->nullable();
            $table->date('next_date');
            $table->boolean('status')->default(true);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->string('currency_code', 3);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->boolean('auto_invoice_generation')->default(true);
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('client_id');

            // Indexes
            $table->index('number');
            $table->index('status');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('frequency');
            $table->index('next_date');
            $table->index(['client_id', 'status']);
            $table->index(['status', 'next_date']);
            $table->index(['company_id', 'client_id']);
            $table->index('archived_at');
            $table->unique(['prefix', 'number']);

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring');
    }
};
