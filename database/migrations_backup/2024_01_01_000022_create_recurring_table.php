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
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');

            // Indexes
            $table->index('number');
            $table->index('status');
            $table->index('client_id');
            $table->index('frequency');
            $table->index('next_date');
            $table->index(['client_id', 'status']);
            $table->index(['status', 'next_date']);
            $table->index('archived_at');
            $table->unique(['prefix', 'number']);
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