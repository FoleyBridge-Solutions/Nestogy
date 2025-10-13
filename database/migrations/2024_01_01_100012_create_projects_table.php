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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('prefix')->nullable();
            $table->integer('number')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('due')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->timestamps();
            $table->string('status')->default('pending');
            $table->integer('progress')->default(0);
            $table->string('priority')->default('medium');
            $table->date('start_date')->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('client_id');

            // Indexes
            $table->index('name');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('manager_id');
            $table->index('due');
            $table->index('completed_at');
            $table->index(['company_id', 'client_id']);
            $table->index('archived_at');
            $table->unique(['prefix', 'number']);

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
