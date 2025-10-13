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
        Schema::create('physical_mail_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('postgrid_id')->nullable()->index();
            $table->foreignId('client_id')->nullable()->constrained('clients');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('province_or_state')->nullable();
            $table->string('postal_or_zip')->nullable();
            $table->string('country_code', 2)->default('US');
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->enum('address_status', ['verified', 'corrected', 'unverified'])->default('unverified');
            $table->json('address_change')->nullable(); // NCOA data
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'company_name']);
            $table->index('address_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_mail_contacts');
    }
};
