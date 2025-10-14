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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('prefix')->nullable();
            $table->integer('number');
            $table->string('scope')->nullable();
            $table->string('status'); // Draft, Sent, Accepted, Declined, Expired
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->date('date');
            $table->date('expire')->nullable();
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->string('currency_code', 3);
            $table->text('note')->nullable();
            $table->string('url_key')->nullable(); // For public access
            $table->timestamps();
            $table->enum('approval_status', [
                'pending',
                'manager_approved',
                'executive_approved',
                'rejected',
                'not_required',
            ])->default('not_required');
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('client_id');

            // Indexes
            $table->index('number');
            $table->index('status');
            $table->index('client_id');
            $table->index('company_id');
            $table->index(['company_id', 'status'], 'quotes_company_status_idx');
            $table->index('approval_status');
            $table->index('date');
            $table->index('expire');
            $table->index(['client_id', 'status']);
            $table->index(['company_id', 'client_id']);
            $table->index('url_key');
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
        Schema::dropIfExists('quotes');
    }
};
