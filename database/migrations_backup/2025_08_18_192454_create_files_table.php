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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');

            // Manual morphs to avoid automatic index creation
            $table->string('fileable_type');
            $table->unsignedBigInteger('fileable_id');

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('original_name');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('file_type')->default('other');
            $table->boolean('is_public')->default(false);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes (with custom names to avoid conflicts)
            $table->index(['company_id'], 'files_company_idx');
            $table->index(['fileable_type', 'fileable_id'], 'files_morph_idx');
            $table->index(['file_type'], 'files_type_idx');
            $table->index(['is_public'], 'files_public_idx');
            $table->index(['uploaded_by'], 'files_uploader_idx');

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
        Schema::dropIfExists('files');
    }
};
