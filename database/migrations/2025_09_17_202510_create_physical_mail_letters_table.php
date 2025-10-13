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
        Schema::create('physical_mail_letters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('to_contact_id')->constrained('physical_mail_contacts');
            $table->foreignUuid('from_contact_id')->constrained('physical_mail_contacts');
            $table->foreignUuid('template_id')->nullable()->constrained('physical_mail_templates');
            $table->text('content')->nullable(); // HTML or PDF URL
            $table->boolean('color')->default(false);
            $table->boolean('double_sided')->default(false);
            $table->enum('address_placement', ['top_first_page', 'insert_blank_page'])->default('top_first_page');
            $table->enum('size', ['us_letter', 'us_legal', 'a4'])->default('us_letter');
            $table->integer('perforated_page')->nullable();
            $table->foreignUuid('return_envelope_id')->nullable()->constrained('physical_mail_return_envelopes');
            $table->enum('extra_service', ['certified', 'certified_return_receipt', 'registered'])->nullable();
            $table->json('merge_variables')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_mail_letters');
    }
};
