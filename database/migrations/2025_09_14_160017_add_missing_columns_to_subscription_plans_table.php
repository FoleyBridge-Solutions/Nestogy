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
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('slug', 50)->unique()->after('name');
            $table->decimal('price_yearly', 10, 2)->nullable()->after('price_monthly');
            $table->string('stripe_price_id_yearly')->nullable()->after('stripe_price_id');
            $table->integer('max_users')->nullable()->after('user_limit');
            $table->integer('max_clients')->nullable()->after('max_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['slug', 'price_yearly', 'stripe_price_id_yearly', 'max_users', 'max_clients']);
        });
    }
};