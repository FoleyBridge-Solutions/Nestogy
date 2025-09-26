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
        Schema::create('custom_quick_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title', 50);
            $table->string('description', 255);
            $table->string('icon', 50)->default('bolt');
            $table->enum('color', ['blue', 'green', 'purple', 'orange', 'red', 'yellow', 'gray'])->default('blue');
            $table->enum('type', ['route', 'url'])->default('route');
            $table->string('target')->comment('Route name or URL');
            $table->json('parameters')->nullable()->comment('Route parameters or URL query params');
            $table->enum('open_in', ['same_tab', 'new_tab'])->default('same_tab');
            $table->enum('visibility', ['private', 'role', 'company'])->default('private');
            $table->json('allowed_roles')->nullable()->comment('Roles that can see this action when visibility is role');
            $table->string('permission')->nullable()->comment('Required permission to use this action');
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'user_id', 'is_active']);
            $table->index(['company_id', 'visibility', 'is_active']);
        });
        
        // Table for tracking user favorites (pinned actions)
        Schema::create('quick_action_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('custom_quick_action_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('system_action')->nullable()->comment('For favoriting system-defined actions');
            $table->integer('position')->default(0);
            $table->timestamps();
            
            $table->unique(['user_id', 'custom_quick_action_id']);
            $table->unique(['user_id', 'system_action']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_action_favorites');
        Schema::dropIfExists('custom_quick_actions');
    }
};
