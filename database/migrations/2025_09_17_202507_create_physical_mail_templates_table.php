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
        Schema::create('physical_mail_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('postgrid_id')->nullable()->unique();
            $table->string('name');
            $table->enum('type', ['letter', 'postcard', 'cheque', 'self_mailer']);
            $table->text('content')->nullable(); // HTML content
            $table->text('description')->nullable();
            $table->json('variables')->nullable(); // List of merge variables
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_mail_templates');
    }
};
