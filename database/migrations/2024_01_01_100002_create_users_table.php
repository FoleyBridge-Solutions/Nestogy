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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('status')->default(true);
            $table->string('token')->nullable();
            $table->string('avatar')->nullable();
            $table->string('specific_encryption_ciphertext')->nullable();
            $table->string('php_session')->nullable();
            $table->string('extension_key', 18)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->string('phone')->nullable();
            $table->string('title')->nullable();
            $table->string('department')->nullable();
            $table->timestamp('archived_at')->nullable();

            // Indexes
            $table->index('email');
            $table->index('status');
            $table->index('company_id');
            $table->index(['email', 'status']);
            $table->index(['company_id', 'status']);

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
