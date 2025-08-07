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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('extension')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('hours')->nullable();
            $table->string('sla')->nullable();
            $table->string('code')->nullable();
            $table->string('account_number')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('template')->default(false);
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('vendors')->onDelete('set null');

            // Indexes
            $table->index('name');
            $table->index('email');
            $table->index('client_id');
            $table->index('template');
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};