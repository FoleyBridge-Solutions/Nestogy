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
        Schema::table('contacts', function (Blueprint $table) {
            // Portal invitation fields
            $table->string('invitation_token', 64)->nullable()->unique()
                ->after('password_hash')
                ->comment('Unique token for portal invitation');
            
            $table->timestamp('invitation_sent_at')->nullable()
                ->after('invitation_token')
                ->comment('When the invitation was sent');
            
            $table->timestamp('invitation_expires_at')->nullable()
                ->after('invitation_sent_at')
                ->comment('When the invitation expires');
            
            $table->timestamp('invitation_accepted_at')->nullable()
                ->after('invitation_expires_at')
                ->comment('When the invitation was accepted');
            
            $table->unsignedBigInteger('invitation_sent_by')->nullable()
                ->after('invitation_accepted_at')
                ->comment('User ID who sent the invitation');
            
            $table->enum('invitation_status', ['pending', 'sent', 'accepted', 'expired', 'revoked'])
                ->nullable()
                ->default(null)
                ->after('invitation_sent_by')
                ->comment('Current status of the invitation');
            
            // Add indexes for performance
            $table->index('invitation_token', 'idx_invitation_token');
            $table->index(['invitation_status', 'invitation_expires_at'], 'idx_invitation_status_expires');
            $table->index(['company_id', 'invitation_status'], 'idx_company_invitation_status');
            
            // Foreign key for sent_by user
            $table->foreign('invitation_sent_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['invitation_sent_by']);
            
            $table->dropIndex('idx_invitation_token');
            $table->dropIndex('idx_invitation_status_expires');
            $table->dropIndex('idx_company_invitation_status');
            
            $table->dropColumn([
                'invitation_token',
                'invitation_sent_at',
                'invitation_expires_at',
                'invitation_accepted_at',
                'invitation_sent_by',
                'invitation_status'
            ]);
        });
    }
};
