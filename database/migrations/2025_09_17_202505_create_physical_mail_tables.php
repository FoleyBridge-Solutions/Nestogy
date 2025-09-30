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
        // Contacts table for physical mail
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

        // Templates for physical mail
        Schema::create('physical_mail_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('postgrid_id')->nullable()->unique();
            $table->string('name');
            $table->enum('type', ['letter', 'postcard', 'cheque', 'self_mailer']);
            $table->text('content')->nullable(); // HTML content
            $table->text('description')->nullable();
            $table->json('variables')->nullable(); // List of merge variables
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('name');
        });

        // Bank accounts for cheques
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

        // Return envelopes inventory
        Schema::create('physical_mail_return_envelopes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('postgrid_id')->nullable()->unique();
            $table->foreignUuid('contact_id')->constrained('physical_mail_contacts');
            $table->integer('quantity_ordered')->default(0);
            $table->integer('quantity_available')->default(0);
            $table->timestamps();

            $table->index('contact_id');
        });

        // Letters
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

        // Postcards
        Schema::create('physical_mail_postcards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('to_contact_id')->constrained('physical_mail_contacts');
            $table->foreignUuid('from_contact_id')->constrained('physical_mail_contacts');
            $table->foreignUuid('template_id')->nullable()->constrained('physical_mail_templates');
            $table->text('front_content')->nullable();
            $table->text('back_content')->nullable();
            $table->enum('size', ['6x4', '9x6', '11x6'])->default('6x4');
            $table->json('merge_variables')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index('created_at');
        });

        // Cheques
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

        // Self mailers
        Schema::create('physical_mail_self_mailers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('to_contact_id')->constrained('physical_mail_contacts');
            $table->foreignUuid('from_contact_id')->constrained('physical_mail_contacts');
            $table->foreignUuid('template_id')->nullable()->constrained('physical_mail_templates');
            $table->text('content')->nullable();
            $table->enum('size', ['8.5x11_bifold', '8.5x11_trifold'])->default('8.5x11_bifold');
            $table->json('merge_variables')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index('created_at');
        });

        // Main orders table (polymorphic)
        Schema::create('physical_mail_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('client_id')->nullable()->constrained('clients');
            $table->string('mailable_type'); // letter/postcard/cheque/self_mailer
            $table->uuid('mailable_id');
            $table->string('postgrid_id')->nullable()->unique();
            $table->enum('status', ['pending', 'ready', 'printing', 'processed_for_delivery', 'completed', 'cancelled', 'failed'])->default('pending');

            // US Intelligent Mail Barcode tracking
            $table->enum('imb_status', ['entered_mail_stream', 'out_for_delivery', 'returned_to_sender'])->nullable();
            $table->timestamp('imb_date')->nullable();
            $table->string('imb_zip_code')->nullable();

            $table->string('tracking_number')->nullable(); // For certified/registered
            $table->string('mailing_class')->default('first_class');
            $table->timestamp('send_date')->nullable();
            $table->decimal('cost', 8, 2)->nullable();
            $table->text('pdf_url')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['mailable_type', 'mailable_id']);
            $table->index(['client_id', 'status']);
            $table->index('postgrid_id');
            $table->index('status');
            $table->index('send_date');
            $table->index('tracking_number');
        });

        // Webhooks log
        Schema::create('physical_mail_webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('postgrid_event_id')->unique();
            $table->string('type'); // letter.created, letter.updated, etc.
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_mail_webhooks');
        Schema::dropIfExists('physical_mail_orders');
        Schema::dropIfExists('physical_mail_self_mailers');
        Schema::dropIfExists('physical_mail_cheques');
        Schema::dropIfExists('physical_mail_postcards');
        Schema::dropIfExists('physical_mail_letters');
        Schema::dropIfExists('physical_mail_return_envelopes');
        Schema::dropIfExists('physical_mail_bank_accounts');
        Schema::dropIfExists('physical_mail_templates');
        Schema::dropIfExists('physical_mail_contacts');
    }
};
