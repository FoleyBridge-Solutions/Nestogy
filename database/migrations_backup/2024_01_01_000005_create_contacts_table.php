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
            $table->string('department')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            // location_id will be added in a later migration to avoid circular dependency
            // vendor_id will be added in a later migration to avoid dependency issues
            $table->foreignId('client_id')->constrained()->onDelete('cascade');

            // Indexes
            $table->index('name');
            $table->index('email');
            $table->index('client_id');
            $table->index('primary');
            $table->index('important');
            $table->index(['client_id', 'primary']);
            $table->index(['client_id', 'billing']);
            $table->index(['client_id', 'technical']);
            $table->index('archived_at');
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