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
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('widget_id')->unique();
            $table->string('name');
            $table->string('category'); // kpi, chart, table, alert, custom
            $table->string('type'); // revenue_chart, ticket_status, client_health, etc.
            $table->text('description')->nullable();
            $table->json('default_config'); // Default settings for the widget
            $table->json('available_sizes'); // ['1x1', '2x1', '2x2', etc.]
            $table->string('data_source'); // api endpoint or service method
            $table->integer('min_refresh_interval')->default(30); // seconds
            $table->json('required_permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('icon')->nullable();
            $table->string('color_scheme')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
