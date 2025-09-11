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
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Display name like "John Doe - Gmail"
            $table->string('email_address');
            $table->string('provider')->default('manual'); // gmail, outlook, yahoo, manual
            
            // IMAP Configuration
            $table->string('imap_host');
            $table->integer('imap_port')->default(993);
            $table->string('imap_encryption')->default('ssl'); // ssl, tls, none
            $table->string('imap_username');
            $table->text('imap_password'); // Encrypted
            $table->boolean('imap_validate_cert')->default(true);
            
            // SMTP Configuration  
            $table->string('smtp_host');
            $table->integer('smtp_port')->default(587);
            $table->string('smtp_encryption')->default('tls');
            $table->string('smtp_username');
            $table->text('smtp_password'); // Encrypted
            
            // OAuth Configuration
            $table->text('oauth_access_token')->nullable();
            $table->text('oauth_refresh_token')->nullable();
            $table->timestamp('oauth_expires_at')->nullable();
            $table->json('oauth_scopes')->nullable();
            
            // Account Settings
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sync_interval_minutes')->default(5); // How often to sync
            $table->timestamp('last_synced_at')->nullable();
            $table->text('sync_error')->nullable();
            
            // Auto-processing settings
            $table->boolean('auto_create_tickets')->default(false);
            $table->boolean('auto_log_communications')->default(true);
            $table->json('filters')->nullable(); // JSON rules for processing
            
            $table->timestamps();
            $table->index(['user_id', 'is_active']);
            $table->unique(['user_id', 'email_address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};
