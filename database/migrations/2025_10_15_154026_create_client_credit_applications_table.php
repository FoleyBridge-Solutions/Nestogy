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
        Schema::create('client_credit_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_credit_id');
            
            $table->morphs('applicable');
            
            $table->decimal('amount', 10, 2);
            $table->date('applied_date');
            $table->unsignedBigInteger('applied_by')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamp('unapplied_at')->nullable();
            $table->unsignedBigInteger('unapplied_by')->nullable();
            $table->text('unapplication_reason')->nullable();
            
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['client_credit_id', 'is_active']);
            $table->index(['company_id', 'applied_date']);
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_credit_id')->references('id')->on('client_credits')->onDelete('cascade');
            $table->foreign('applied_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('unapplied_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_credit_applications');
    }
};
