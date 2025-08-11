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
        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->default('general'); // general, contract, invoice, legal, technical
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_size');
            $table->string('mime_type');
            $table->string('version', 20)->default('1.0');
            $table->boolean('is_confidential')->default(false);
            $table->date('expiry_date')->nullable();
            $table->json('tags')->nullable();
            $table->integer('download_count')->default(0);
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'category']);
            $table->index(['company_id', 'is_confidential']);
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_documents');
    }
};
