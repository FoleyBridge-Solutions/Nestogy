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
        Schema::create('ticket_comment_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_comment_id');
            $table->unsignedBigInteger('company_id');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->longText('content'); // Base64 encoded content
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('ticket_comment_id');
            $table->index('company_id');
            $table->index(['ticket_comment_id', 'company_id']);

            // Foreign keys
            $table->foreign('ticket_comment_id')->references('id')->on('ticket_comments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_comment_attachments');
    }
};
