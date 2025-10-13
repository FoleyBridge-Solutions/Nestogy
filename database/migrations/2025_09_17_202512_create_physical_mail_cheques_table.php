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
        Schema::create('physical_mail_cheques', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('to_contact_id')->constrained('physical_mail_contacts');
            $table->foreignUuid('from_contact_id')->constrained('physical_mail_contacts');
            $table->foreignUuid('bank_account_id')->constrained('physical_mail_bank_accounts');
            $table->decimal('amount', 10, 2);
            $table->string('memo')->nullable();
            $table->text('message_content')->nullable();
            $table->string('check_number')->nullable();
            $table->boolean('digital_only')->default(false);
            $table->enum('size', ['us_letter', 'us_legal'])->default('us_letter');
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index('created_at');
            $table->index('check_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_mail_cheques');
    }
};
