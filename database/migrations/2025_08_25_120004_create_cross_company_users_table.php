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
        Schema::create('cross_company_users', function (Blueprint $table) {
            $table->id();
            
            // User and company relationship
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('company_id')->index(); // Company they can access
            $table->unsignedBigInteger('primary_company_id')->index(); // Their home company
            
            // Access configuration
            $table->integer('role_in_company'); // Role in this specific company
            $table->enum('access_type', ['full', 'limited', 'view_only'])->default('limited');
            $table->json('access_permissions')->nullable(); // Specific permissions
            $table->json('access_restrictions')->nullable(); // What they cannot do
            
            // Delegation and authorization
            $table->unsignedBigInteger('authorized_by')->nullable(); // Who granted access
            $table->unsignedBigInteger('delegated_from')->nullable(); // If delegated from another user
            $table->text('authorization_reason')->nullable();
            
            // Access management
            $table->boolean('is_active')->default(true);
            $table->timestamp('access_granted_at')->nullable();
            $table->timestamp('access_expires_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            
            // Session management
            $table->boolean('require_re_auth')->default(false); // Require auth when switching
            $table->integer('max_concurrent_sessions')->default(1);
            $table->json('allowed_features')->nullable(); // Feature restrictions
            
            // Audit and compliance
            $table->boolean('audit_actions')->default(true); // Log all actions in this company
            $table->json('compliance_settings')->nullable();
            $table->text('notes')->nullable();
            
            // Timestamps and audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
                
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
                
            $table->foreign('primary_company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
                
            $table->foreign('authorized_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
                
            $table->foreign('delegated_from')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
                
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
                
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // Unique constraint to prevent duplicate access grants
            $table->unique(['user_id', 'company_id'], 'unique_cross_company_user');
            
            // Composite indexes for performance
            $table->index(['user_id', 'is_active'], 'cross_company_user_active_idx');
            $table->index(['company_id', 'access_type'], 'cross_company_access_type_idx');
            $table->index(['primary_company_id', 'user_id'], 'cross_company_primary_idx');
            $table->index(['access_expires_at'], 'cross_company_expires_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cross_company_users');
    }
};