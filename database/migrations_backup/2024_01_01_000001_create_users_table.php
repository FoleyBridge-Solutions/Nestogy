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
            $table->timestamp('archived_at')->nullable();

            // Indexes
            $table->index('email');
            $table->index('status');
            $table->index(['email', 'status']);
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