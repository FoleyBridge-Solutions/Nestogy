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
        Schema::create('user_widget_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_dashboard_config_id')->constrained()->onDelete('cascade');
            $table->foreignId('dashboard_widget_id')->constrained()->onDelete('cascade');
            $table->string('instance_id')->unique();
            $table->integer('position_x');
            $table->integer('position_y');
            $table->integer('width');
            $table->integer('height');
            $table->json('custom_config')->nullable(); // User-specific widget settings
            $table->json('filters')->nullable(); // Applied filters
            $table->integer('refresh_interval')->nullable(); // Override default
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_collapsed')->default(false);
            $table->timestamps();

            $table->index('instance_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_widget_instances');
    }
};
