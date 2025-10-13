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
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
                        $table->string('name');
                        $table->string('email')->nullable();
                        $table->string('password')->nullable();
                        $table->string('role')->default('viewer');
                        $table->integer('session_timeout_minutes')->default(30);
                        $table->json('notification_preferences')->nullable();
                        $table->timestamps();
                        $table->softDeletes();
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
