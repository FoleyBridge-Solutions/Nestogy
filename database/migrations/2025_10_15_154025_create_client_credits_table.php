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
        Schema::create('client_credits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            
            $table->morphs('source');
            
            $table->decimal('amount', 10, 2);
            $table->decimal('used_amount', 10, 2)->default(0);
            $table->decimal('available_amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            
            $table->enum('type', [
                'overpayment',
                'prepayment',
                'credit_note',
                'promotional',
                'goodwill',
                'refund_credit',
                'adjustment'
            ]);
            
            $table->enum('status', ['active', 'depleted', 'expired', 'voided'])->default('active');
            $table->date('credit_date');
            $table->date('expiry_date')->nullable();
            $table->timestamp('depleted_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            
            $table->string('reference_number')->unique();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['client_id', 'status']);
            $table->index(['company_id', 'credit_date']);
            $table->index(['expiry_date', 'status']);
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_credits');
    }
};
