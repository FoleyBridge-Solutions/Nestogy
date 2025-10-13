<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drop legacy permission system tables now that we've migrated to Bouncer.
     * This migration should only be run after confirming all data has been
     * successfully migrated to Bouncer tables.
     */
    public function up(): void
    {
        // Only run if Bouncer tables exist, otherwise skip this migration
        if (! Schema::hasTable('bouncer_abilities') || ! Schema::hasTable('bouncer_roles')) {
            echo "Bouncer tables not found yet. Skipping legacy permission table cleanup.\n";

            return;
        }

        // Drop legacy permission system tables in reverse dependency order
        if (Schema::hasTable('user_permissions')) {
            Schema::drop('user_permissions');
        }

        if (Schema::hasTable('role_permissions')) {
            Schema::drop('role_permissions');
        }

        if (Schema::hasTable('user_roles')) {
            Schema::drop('user_roles');
        }

        if (Schema::hasTable('permissions')) {
            Schema::drop('permissions');
        }

        if (Schema::hasTable('roles')) {
            Schema::drop('roles');
        }

        // Don't drop permission_groups yet - tests still reference it
        // if (Schema::hasTable('permission_groups')) {
        //     Schema::drop('permission_groups');
        // }

        echo "Dropped legacy permission system tables.\n";
        echo "The system now uses Bouncer for role and permission management.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate basic table structures for rollback
        // Note: This won't restore data, only table structure

        Schema::create('permission_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain');
            $table->string('action');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->foreignId('group_id')->nullable()->constrained('permission_groups');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('level')->default(1);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'role_id', 'company_id']);
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->boolean('granted')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'permission_id', 'company_id']);
        });

        echo "Recreated legacy permission table structures (no data restored).\n";
    }
};
