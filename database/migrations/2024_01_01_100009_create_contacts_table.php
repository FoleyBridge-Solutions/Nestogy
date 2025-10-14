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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('extension')->nullable();
            $table->string('mobile')->nullable();
            $table->string('photo')->nullable();
            $table->string('pin')->nullable();
            $table->text('notes')->nullable();
            $table->string('auth_method')->nullable();
            $table->string('password_hash')->nullable();
            $table->string('password_reset_token')->nullable();
            $table->timestamp('token_expire')->nullable();
            $table->boolean('primary')->default(false);
            $table->boolean('important')->default(false);
            $table->boolean('billing')->default(false);
            $table->boolean('technical')->default(false);

            // Portal access fields
            $table->boolean('has_portal_access')->default(false);
            $table->json('portal_permissions')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->integer('login_count')->default(0);
            $table->integer('failed_login_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->integer('session_timeout_minutes')->default(30);
            $table->json('allowed_ip_addresses')->nullable();

            $table->string('department')->nullable();
            $table->timestamps();
            $table->string('preferred_contact_method', 50)->nullable()->default('email');
            $table->string('best_time_to_contact', 50)->nullable()->default('anytime');
            $table->string('timezone', 100)->nullable();
            $table->string('language', 50)->nullable()->default('en');
            $table->boolean('do_not_disturb')->default(false);
            $table->boolean('marketing_opt_in')->default(false);
            $table->string('linkedin_url')->nullable();
            $table->string('assistant_name')->nullable();
            $table->string('assistant_email')->nullable();
            $table->string('assistant_phone', 50)->nullable();
            $table->unsignedBigInteger('reports_to_id')->nullable();
            $table->text('work_schedule')->nullable();
            $table->text('professional_bio')->nullable();
            $table->unsignedBigInteger('office_location_id')->nullable();
            $table->boolean('is_emergency_contact')->default(false);
            $table->boolean('is_after_hours_contact')->default(false);
            $table->date('out_of_office_start')->nullable();
            $table->date('out_of_office_end')->nullable();
            $table->string('website')->nullable();
            $table->string('twitter_handle', 100)->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_handle', 100)->nullable();
            $table->string('company_blog')->nullable();
            $table->string('role')->nullable();
            $table->timestamp('invitation_sent_at')->nullable()

                ->comment('When the invitation was sent');
            $table->timestamp('invitation_expires_at')->nullable()

                ->comment('When the invitation expires');
            $table->timestamp('invitation_accepted_at')->nullable()

                ->comment('When the invitation was accepted');
            $table->unsignedBigInteger('invitation_sent_by')->nullable()

                ->comment('User ID who sent the invitation');
            $table->enum('invitation_status', ['pending', 'sent', 'accepted', 'expired', 'revoked'])
                ->nullable()
                ->default(null)

                ->comment('Current status of the invitation');
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();

            // Indexes
            $table->index('name');
            $table->index('email');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('primary');
            $table->index('important');
            $table->index(['client_id', 'primary']);
            $table->index(['client_id', 'billing']);
            $table->index(['client_id', 'technical']);
            $table->index(['company_id', 'client_id']);
            $table->index('archived_at');
            $table->index(['company_id', 'email', 'has_portal_access'], 'idx_portal_access');
            $table->index(['company_id', 'client_id', 'has_portal_access'], 'idx_client_portal');

            $table->index('preferred_contact_method');
            $table->index('timezone');
            $table->index('language');
            $table->index('is_emergency_contact');
            $table->index('is_after_hours_contact');
            $table->string('invitation_token', 64)->nullable()->unique()

                ->comment('Unique token for portal invitation');
            $table->index('invitation_token', 'idx_invitation_token');
            $table->index(['invitation_status', 'invitation_expires_at'], 'idx_invitation_status_expires');
            $table->index(['company_id', 'invitation_status'], 'idx_company_invitation_status');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
        });

        // Add contact_id foreign key to locations now that contacts table exists
        Schema::table('locations', function (Blueprint $table) {
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
