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
        Schema::create('ticket_workflows', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->string('name');
                        $table->text('description')->nullable();
                        $table->json('conditions');
                        $table->json('actions');
                        $table->boolean('is_active')->default(true);
                        $table->integer('sort_order')->default(0);
                        $table->timestamps();

                        $table->index(['company_id', 'is_active']);
                        $table->index(['company_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_workflows');
    }
};
