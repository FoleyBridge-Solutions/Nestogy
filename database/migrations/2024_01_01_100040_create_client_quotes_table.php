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
        Schema::create('client_quotes', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->constrained()->onDelete('cascade');
                        $table->string('quote_number');
                        $table->decimal('subtotal', 10, 2);
                        $table->decimal('tax_amount', 10, 2)->default(0);
                        $table->decimal('total', 10, 2);
                        $table->date('quote_date');
                        $table->date('expiry_date')->nullable();
                        $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
                        $table->json('line_items');
                        $table->text('notes')->nullable();
                        $table->text('terms')->nullable();
                        $table->timestamps();

                        $table->index(['company_id', 'client_id']);
                        $table->index(['company_id', 'quote_date']);
                        $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_quotes');
    }
};
