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
        Schema::create('kb_article_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('kb_articles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('client_contacts')->nullOnDelete();
            $table->boolean('is_helpful');
            $table->text('feedback_text')->nullable();
            $table->enum('feedback_type', ['rating', 'comment', 'suggestion', 'report'])->default('rating');
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'article_id']);
            $table->index(['article_id', 'is_helpful']);
            $table->index(['user_id']);
            $table->index(['contact_id']);
            $table->index(['feedback_type']);
            $table->unique(['article_id', 'user_id', 'feedback_type'], 'unique_user_article_feedback');
            $table->unique(['article_id', 'contact_id', 'feedback_type'], 'unique_contact_article_feedback');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_article_feedback');
    }
};