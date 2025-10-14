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
        Schema::create('physical_mail_bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('postgrid_id')->nullable()->unique();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('account_name');
            $table->text('account_number'); // Encrypted
            $table->text('routing_number'); // Encrypted
            $table->string('bank_name')->nullable();
            $table->text('signature_image')->nullable(); // Base64 or URL
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_mail_bank_accounts');
    }
};
