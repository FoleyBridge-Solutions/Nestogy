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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_id');
            $table->integer('role')->default(1); // 1=Accountant, 2=Tech, 3=Admin
            $table->string('remember_me_token')->nullable();
            $table->boolean('force_mfa')->default(false);
            $table->integer('records_per_page')->default(10);
            $table->boolean('dashboard_financial_enable')->default(false);
            $table->boolean('dashboard_technical_enable')->default(false);
            $table->timestamps();
            $table->string('theme', 20)->default('light');
            $table->json('preferences')->nullable();

            // Indexes
            $table->index('user_id');
            $table->index('company_id');
            $table->index('role');
            $table->index(['user_id', 'role']);
            $table->index(['company_id', 'role']);

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
