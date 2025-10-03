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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->integer('type')->default(1);
            $table->string('plaid_id')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
