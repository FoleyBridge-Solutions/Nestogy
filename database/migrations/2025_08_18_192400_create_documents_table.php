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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            
            // Manual morphs to avoid automatic index creation
            $table->string('documentable_type');
            $table->unsignedBigInteger('documentable_id');
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('category')->default('other');
            $table->boolean('is_private')->default(false);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes (with custom names to avoid conflicts)
            $table->index(['company_id'], 'docs_company_idx');
            $table->index(['documentable_type', 'documentable_id'], 'docs_morph_idx');
            $table->index(['category'], 'docs_category_idx');
            $table->index(['is_private'], 'docs_private_idx');
            $table->index(['uploaded_by'], 'docs_uploader_idx');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
