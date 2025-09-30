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
        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_message_id')->constrained()->cascadeOnDelete();

            $table->string('filename');
            $table->string('content_type');
            $table->integer('size_bytes');
            $table->string('content_id')->nullable(); // For inline attachments
            $table->boolean('is_inline')->default(false);
            $table->string('encoding')->nullable(); // base64, quoted-printable, etc
            $table->string('disposition')->default('attachment'); // attachment, inline

            // Storage information
            $table->string('storage_disk')->default('local'); // local, s3, etc
            $table->string('storage_path'); // Path to stored file
            $table->string('hash')->nullable(); // File hash for deduplication

            // Preview/thumbnail info
            $table->boolean('is_image')->default(false);
            $table->string('thumbnail_path')->nullable();
            $table->json('metadata')->nullable(); // Image dimensions, etc

            $table->timestamps();

            $table->index(['email_message_id']);
            $table->index(['content_type']);
            $table->index(['hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
    }
};
