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
        Schema::create('client_domains', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->constrained()->onDelete('cascade');
                        $table->string('domain');
                        $table->string('registrar')->nullable();
                        $table->date('registration_date')->nullable();
                        $table->date('expiry_date')->nullable();
                        $table->boolean('auto_renew')->default(false);
                        $table->enum('status', ['active', 'expired', 'pending', 'suspended'])->default('active');
                        $table->text('notes')->nullable();
                        $table->json('dns_records')->nullable();
                        $table->timestamps();

                        $table->index(['company_id', 'client_id']);
                        $table->index(['company_id', 'expiry_date']);
                        $table->index(['company_id', 'domain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_domains');
    }
};
