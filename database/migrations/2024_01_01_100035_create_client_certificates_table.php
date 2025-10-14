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
        Schema::create('client_certificates', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->constrained()->onDelete('cascade');
                        $table->string('name');
                        $table->string('domain');
                        $table->string('issuer')->nullable();
                        $table->date('issue_date')->nullable();
                        $table->date('expiry_date');
                        $table->enum('type', ['ssl', 'wildcard', 'ev', 'dv', 'ov'])->default('ssl');
                        $table->enum('status', ['active', 'expired', 'pending', 'revoked'])->default('active');
                        $table->text('notes')->nullable();
                        $table->json('metadata')->nullable();
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
        Schema::dropIfExists('client_certificates');
    }
};
