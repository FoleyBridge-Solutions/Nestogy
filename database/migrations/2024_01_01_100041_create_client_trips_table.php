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
        Schema::create('client_trips', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->constrained()->onDelete('cascade');
                        $table->string('purpose');
                        $table->text('description')->nullable();
                        $table->dateTime('start_time');
                        $table->dateTime('end_time')->nullable();
                        $table->decimal('mileage', 8, 2)->nullable();
                        $table->decimal('expense_amount', 10, 2)->default(0);
                        $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
                        $table->json('expenses')->nullable();
                        $table->timestamps();

                        $table->index(['company_id', 'client_id']);
                        $table->index(['company_id', 'start_time']);
                        $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_trips');
    }
};
