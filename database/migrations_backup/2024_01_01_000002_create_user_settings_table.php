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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('role')->default(1); // 1=Accountant, 2=Tech, 3=Admin
            $table->string('remember_me_token')->nullable();
            $table->boolean('force_mfa')->default(false);
            $table->integer('records_per_page')->default(10);
            $table->boolean('dashboard_financial_enable')->default(false);
            $table->boolean('dashboard_technical_enable')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('role');
            $table->index(['user_id', 'role']);
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