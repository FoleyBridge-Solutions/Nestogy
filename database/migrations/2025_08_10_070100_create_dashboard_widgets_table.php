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
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('user_id')->nullable(); // Null for company-wide widgets
            $table->string('widget_type'); // 'revenue_chart', 'kpi_card', 'table', 'gauge', etc.
            $table->string('widget_name');
            $table->text('description')->nullable();
            $table->string('dashboard_type'); // 'executive', 'revenue', 'operations', 'customer', 'forecasting'
            $table->json('configuration'); // Widget-specific settings, queries, filters
            $table->json('display_settings'); // Size, position, colors, formatting
            $table->json('data_source'); // Query configuration, data connections
            $table->json('refresh_settings'); // Auto-refresh intervals, cache settings
            $table->json('permissions')->nullable(); // Role-based access controls
            $table->integer('sort_order')->default(0);
            $table->integer('grid_row')->nullable();
            $table->integer('grid_column')->nullable();
            $table->integer('grid_width')->default(4); // Grid units (1-12)
            $table->integer('grid_height')->default(4); // Grid units
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Default widgets for new users
            $table->timestamp('last_updated_at')->nullable();
            $table->json('metadata')->nullable(); // Additional widget metadata
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['company_id', 'dashboard_type', 'is_active']);
            $table->index(['user_id', 'dashboard_type', 'sort_order']);
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