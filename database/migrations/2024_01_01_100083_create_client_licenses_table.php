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
        Schema::create('client_licenses', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->constrained()->onDelete('cascade');
                        $table->string('software_name');
                        $table->string('license_key');
                        $table->string('version')->nullable();
                        $table->integer('seats')->default(1);
                        $table->date('purchase_date')->nullable();
                        $table->date('expiry_date')->nullable();
                        $table->decimal('cost', 10, 2)->nullable();
                        $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
                        $table->text('notes')->nullable();
                        $table->timestamps();

                        $table->index(['company_id', 'client_id']);
                        $table->index(['company_id', 'expiry_date']);
                        $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_licenses');
    }
};
