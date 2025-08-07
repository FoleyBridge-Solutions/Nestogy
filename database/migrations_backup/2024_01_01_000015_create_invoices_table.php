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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('prefix')->nullable();
            $table->integer('number');
            $table->string('scope')->nullable();
            $table->string('status'); // Draft, Sent, Paid, Overdue, Cancelled
            $table->date('date');
            $table->date('due');
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->string('currency_code', 3);
            $table->text('note')->nullable();
            $table->string('url_key')->nullable(); // For public access
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');

            // Indexes
            $table->index('number');
            $table->index('status');
            $table->index('client_id');
            $table->index('date');
            $table->index('due');
            $table->index(['client_id', 'status']);
            $table->index('url_key');
            $table->index('archived_at');
            $table->unique(['prefix', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};