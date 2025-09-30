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
        Schema::create('project_time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('task_id')->nullable()->constrained('project_tasks')->nullOnDelete();
            $table->text('description')->nullable();
            $table->decimal('hours', 8, 2);
            $table->date('date');
            $table->boolean('billable')->default(true);
            $table->boolean('billed')->default(false);
            $table->decimal('rate', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['project_id', 'date']);
            $table->index(['user_id', 'date']);
            $table->index('billable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_time_entries');
    }
};
