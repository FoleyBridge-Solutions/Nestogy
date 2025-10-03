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
        Schema::create('client_portal_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->string('phone')->nullable();
            $table->string('title');
            $table->string('department')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->string('can_view_invoices')->nullable();
            $table->string('can_view_tickets')->nullable();
            $table->string('can_create_tickets')->nullable();
            $table->string('can_view_assets')->nullable();
            $table->string('can_view_projects')->nullable();
            $table->string('can_view_reports')->nullable();
            $table->string('can_approve_quotes')->nullable();
            $table->string('notification_preferences')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->string('login_count')->nullable();
            $table->string('failed_login_count')->nullable();
            $table->string('locked_until')->nullable();
            $table->string('email_verified_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->string('must_change_password')->nullable();
            $table->string('session_timeout_minutes')->nullable();
            $table->string('allowed_ip_addresses')->nullable();
            $table->string('timezone')->nullable();
            $table->string('locale')->nullable();
            $table->string('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes('archived_at');
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_portal_users');
    }
};
