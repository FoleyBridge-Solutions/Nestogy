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
        // Drop existing tables if they exist
        Schema::dropIfExists('dashboard_activity_logs');
        Schema::dropIfExists('dashboard_metrics');
        Schema::dropIfExists('widget_data_cache');
        Schema::dropIfExists('dashboard_presets');
        Schema::dropIfExists('user_widget_instances');
        Schema::dropIfExists('user_dashboard_configs');
        Schema::dropIfExists('dashboard_widgets');
        
        // Dashboard widget definitions
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

        // User dashboard configurations
        Schema::create('user_dashboard_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('dashboard_name')->default('main');
            $table->json('layout'); // Grid layout configuration
            $table->json('widgets'); // Active widgets and their positions
            $table->json('preferences'); // Theme, refresh rates, etc.
            $table->boolean('is_default')->default(false);
            $table->boolean('is_shared')->default(false);
            $table->timestamps();
            
            $table->unique(['user_id', 'dashboard_name']);
            $table->index(['company_id', 'is_shared']);
        });

        // User widget instances
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

        // Dashboard presets
        Schema::create('dashboard_presets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('role')->nullable(); // admin, tech, accountant, executive
            $table->json('layout');
            $table->json('widgets');
            $table->json('default_preferences');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->index(['company_id', 'role']);
        });

        // Widget data cache
        Schema::create('widget_data_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('widget_type');
            $table->string('cache_key')->unique();
            $table->json('data');
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['company_id', 'widget_type']);
            $table->index('expires_at');
        });

        // Real-time metrics tracking
        Schema::create('dashboard_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('metric_key');
            $table->decimal('value', 20, 4);
            $table->decimal('previous_value', 20, 4)->nullable();
            $table->decimal('change_percentage', 8, 2)->nullable();
            $table->string('trend')->nullable(); // up, down, stable
            $table->json('breakdown')->nullable(); // Detailed breakdown
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->index(['company_id', 'metric_key', 'calculated_at']);
        });

        // User dashboard activity log
        Schema::create('dashboard_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('action'); // view, customize, export, share
            $table->json('details')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_activity_logs');
        Schema::dropIfExists('dashboard_metrics');
        Schema::dropIfExists('widget_data_cache');
        Schema::dropIfExists('dashboard_presets');
        Schema::dropIfExists('user_widget_instances');
        Schema::dropIfExists('user_dashboard_configs');
        Schema::dropIfExists('dashboard_widgets');
    }
};