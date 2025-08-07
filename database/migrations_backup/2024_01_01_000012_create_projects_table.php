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
            $table->string('prefix')->nullable();
            $table->integer('number')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('due')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');

            // Indexes
            $table->index('name');
            $table->index('client_id');
            $table->index('manager_id');
            $table->index('due');
            $table->index('completed_at');
            $table->index('archived_at');
            $table->unique(['prefix', 'number']);
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