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
        Schema::create('company_mail_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique();

            // Mail driver configuration
            $table->enum('driver', ['smtp', 'ses', 'mailgun', 'postmark', 'sendgrid', 'log'])->default('smtp');
            $table->boolean('is_active')->default(true);

            // SMTP Settings
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->enum('smtp_encryption', ['tls', 'ssl', 'none'])->nullable();
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable(); // Will be encrypted
            $table->integer('smtp_timeout')->default(30);

            // AWS SES Settings
            $table->string('ses_key')->nullable();
            $table->text('ses_secret')->nullable(); // Will be encrypted
            $table->string('ses_region')->default('us-east-1');

            // Mailgun Settings
            $table->string('mailgun_domain')->nullable();
            $table->text('mailgun_secret')->nullable(); // Will be encrypted
            $table->string('mailgun_endpoint')->default('api.mailgun.net');

            // Postmark Settings
            $table->text('postmark_token')->nullable(); // Will be encrypted

            // SendGrid Settings
            $table->text('sendgrid_api_key')->nullable(); // Will be encrypted

            // Default sender
            $table->string('from_email');
            $table->string('from_name');
            $table->string('reply_to_email')->nullable();
            $table->string('reply_to_name')->nullable();

            // Rate limiting
            $table->integer('rate_limit_per_minute')->default(30);
            $table->integer('rate_limit_per_hour')->default(500);
            $table->integer('rate_limit_per_day')->default(5000);

            // Features
            $table->boolean('track_opens')->default(true);
            $table->boolean('track_clicks')->default(true);
            $table->boolean('auto_retry_failed')->default(true);
            $table->integer('max_retry_attempts')->default(3);

            // Testing
            $table->timestamp('last_test_at')->nullable();
            $table->boolean('last_test_successful')->nullable();
            $table->text('last_test_error')->nullable();

            // Backup mail configuration (fallback)
            $table->json('fallback_config')->nullable();

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_mail_settings');
    }
};
