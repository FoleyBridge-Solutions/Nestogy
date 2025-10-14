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
        Schema::create('ticket_templates', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->string('name');
                        $table->text('description')->nullable();
                        $table->string('category')->nullable();
                        $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
                        $table->json('default_fields')->nullable();
                        $table->text('instructions')->nullable();
                        $table->boolean('is_active')->default(true);
                        $table->timestamps();

                        $table->index(['company_id', 'is_active']);
                        $table->index(['company_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_templates');
    }
};
