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
        Schema::create('subsidiary_permissions', function (Blueprint $table) {
            $table->id();

            // Permission relationship
            $table->unsignedBigInteger('granter_company_id')->index(); // Company granting permission
            $table->unsignedBigInteger('grantee_company_id')->index(); // Company receiving permission
            $table->unsignedBigInteger('user_id')->nullable()->index(); // Specific user (null = all users)

            // Permission details
            $table->string('resource_type'); // Model class or resource identifier
            $table->string('permission_type'); // view, create, edit, delete, manage
            $table->json('conditions')->nullable(); // Additional conditions/filters

            // Scope and context
            $table->enum('scope', ['all', 'specific', 'filtered'])->default('all');
            $table->json('scope_filters')->nullable(); // Specific filters for 'filtered' scope
            $table->text('resource_ids')->nullable(); // Specific resource IDs for 'specific' scope

            // Permission metadata
            $table->boolean('is_inherited')->default(false); // Permission came from parent
            $table->string('inherited_from')->nullable(); // Source of inheritance
            $table->boolean('can_delegate')->default(false); // Can grant to subsidiaries
            $table->integer('priority')->default(0); // Permission priority for conflicts

            // Validity and expiration
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('granter_company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('grantee_company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Unique constraint to prevent duplicate permissions
            $table->unique([
                'granter_company_id',
                'grantee_company_id',
                'user_id',
                'resource_type',
                'permission_type',
            ], 'unique_subsidiary_permission');

            // Composite indexes for performance
            $table->index(['granter_company_id', 'resource_type'], 'permissions_granter_resource_idx');
            $table->index(['grantee_company_id', 'user_id'], 'permissions_grantee_user_idx');
            $table->index(['is_active', 'expires_at'], 'permissions_active_expires_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subsidiary_permissions');
    }
};
