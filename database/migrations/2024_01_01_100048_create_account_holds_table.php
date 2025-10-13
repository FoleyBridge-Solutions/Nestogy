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
        Schema::create('account_holds', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
                        $table->string('hold_reference')->unique()->nullable();
                        $table->string('name');
                        $table->string('hold_type')->nullable();
                        $table->string('status')->default('pending');
                        $table->integer('created_by')->nullable();
                        $table->integer('grace_period_hours')->default(0);
                        $table->timestamp('grace_period_expires_at')->nullable();
                        $table->boolean('resulted_in_payment')->default(false);
                        $table->decimal('payment_amount_received', 10, 2)->nullable();
                        $table->timestamps();
                        $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_holds');
    }
};
