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
        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('client_id');
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
            $table->softDeletes();
            $table->timestamp('accessed_at')->nullable();

            // Indexes
            $table->index('tenant_id');
            $table->index('client_id');
            $table->index('name');
            $table->index('email');
            $table->index('primary');
            $table->index('important');
            $table->index('billing');
            $table->index('technical');
            $table->index(['tenant_id', 'client_id']);
            $table->index(['client_id', 'primary']);
            $table->index(['client_id', 'billing']);
            $table->index(['client_id', 'technical']);
            $table->index('deleted_at');
            $table->index('accessed_at');

            // Foreign key constraints
            $table->foreign('tenant_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_contacts');
    }
};