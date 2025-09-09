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
        Schema::create('contract_action_buttons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('label', 100);
            $table->string('slug', 100);
            $table->string('icon', 50)->nullable();
            $table->string('button_class', 100)->default('btn btn-primary');
            $table->string('action_type', 50); // 'status_change', 'route', 'ajax', 'modal', 'download'
            $table->json('action_config'); // Configuration for the action
            $table->json('visibility_conditions')->nullable(); // When to show the button
            $table->json('permissions')->nullable(); // Required permissions
            $table->text('confirmation_message')->nullable(); // Optional confirmation prompt
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_action_buttons');
    }
};