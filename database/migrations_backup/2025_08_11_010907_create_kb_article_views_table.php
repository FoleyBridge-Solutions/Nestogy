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
        Schema::create('kb_article_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('kb_articles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('client_contacts')->nullOnDelete();
            $table->enum('viewer_type', ['anonymous', 'user', 'client'])->default('anonymous');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('search_query', 500)->nullable();
            $table->integer('time_spent_seconds')->default(0);
            $table->boolean('led_to_ticket')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('viewed_at');
            $table->timestamps();

            $table->index(['company_id', 'article_id']);
            $table->index(['company_id', 'viewer_type']);
            $table->index(['article_id', 'viewed_at']);
            $table->index(['user_id', 'viewed_at']);
            $table->index(['contact_id', 'viewed_at']);
            $table->index(['led_to_ticket']);
            $table->index(['viewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_article_views');
    }
};