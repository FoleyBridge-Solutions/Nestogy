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
        Schema::create('physical_mail_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // API Configuration
            $table->string('test_key')->nullable()->encrypted();
            $table->string('live_key')->nullable()->encrypted();
            $table->string('webhook_secret')->nullable()->encrypted();
            $table->boolean('force_test_mode')->default(false);

            // From Address
            $table->string('from_company_name')->nullable();
            $table->string('from_contact_name')->nullable();
            $table->string('from_address_line1')->nullable();
            $table->string('from_address_line2')->nullable();
            $table->string('from_city')->nullable();
            $table->string('from_state', 2)->nullable();
            $table->string('from_zip', 10)->nullable();
            $table->string('from_country', 2)->default('US');

            // Default Options
            $table->string('default_mailing_class')->default('first_class');
            $table->boolean('default_color_printing')->default(true);
            $table->boolean('default_double_sided')->default(false);
            $table->string('default_address_placement')->default('top_first_page');
            $table->string('default_size')->default('us_letter');

            // Tracking & Billing
            $table->boolean('track_costs')->default(true);
            $table->decimal('markup_percentage', 5, 2)->default(0);
            $table->boolean('include_tax')->default(false);

            // Features
            $table->boolean('enable_ncoa')->default(true); // National Change of Address
            $table->boolean('enable_address_verification')->default(true);
            $table->boolean('enable_return_envelopes')->default(false);
            $table->boolean('enable_bulk_mail')->default(true);

            // Metadata
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_connection_test')->nullable();
            $table->string('last_connection_status')->nullable();

            $table->timestamps();

            // Unique constraint - one setting per company
            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_mail_settings');
    }
};
