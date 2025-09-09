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
        Schema::create('contract_dashboard_widgets', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->unsignedBigInteger('company_id')->index();
            
            // Widget configuration
            $table->string('widget_slug')->index();
            $table->string('widget_type'); // summary, chart, table, metric, etc.
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('config')->nullable(); // Widget-specific configuration
            $table->json('data_source_config')->nullable(); // How to fetch data
            $table->json('display_config')->nullable(); // How to display the widget
            $table->json('filter_config')->nullable(); // Available filters
            $table->json('contract_types_filter')->nullable(); // Which contract types to include
            $table->integer('position_x')->default(0); // Grid position
            $table->integer('position_y')->default(0);
            $table->integer('width')->default(1); // Grid width
            $table->integer('height')->default(1); // Grid height
            $table->json('permissions')->nullable(); // Required permissions
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            
            // Indexes
            $table->index(['company_id', 'widget_slug']);
            $table->index(['company_id', 'widget_type']);
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'position_x', 'position_y']);
            
            // Unique constraint
            $table->unique(['company_id', 'widget_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_dashboard_widgets');
    }
};