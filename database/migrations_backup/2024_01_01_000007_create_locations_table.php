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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('country')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('phone')->nullable();
            $table->string('hours')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            // contact_id will be added in a later migration to avoid circular dependency
            $table->foreignId('client_id')->constrained()->onDelete('cascade');

            // Indexes
            $table->index('name');
            $table->index('client_id');
            $table->index('primary');
            $table->index(['client_id', 'primary']);
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};