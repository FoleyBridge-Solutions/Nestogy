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
        Schema::create('contract_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('negotiation_id')->nullable();
            $table->unsignedBigInteger('version_id')->nullable();

            // Comment content and context
            $table->text('content');
            $table->string('comment_type')->default('general'); // general, suggestion, objection, approval, question
            $table->string('section')->nullable(); // Which section of contract this relates to
            $table->json('context')->nullable(); // Additional context (line numbers, component IDs, etc.)

            // Threading and replies
            $table->unsignedBigInteger('parent_id')->nullable(); // For threaded conversations
            $table->integer('thread_position')->default(0);

            // Visibility and permissions
            $table->boolean('is_internal')->default(true); // Internal vs client-visible
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();

            // Priority and flags
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->boolean('requires_response')->default(false);
            $table->timestamp('response_due')->nullable();

            // Mentions and notifications
            $table->json('mentions')->nullable(); // User IDs mentioned in comment
            $table->json('attachments')->nullable(); // File attachments

            // Author and audit
            $table->unsignedBigInteger('user_id');
            $table->string('author_type')->default('internal'); // internal, client, system
            $table->timestamps();

            // Foreign keys
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
            $table->foreign('negotiation_id')->references('id')->on('contract_negotiations')->onDelete('cascade');
            $table->foreign('version_id')->references('id')->on('contract_versions')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('contract_comments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['contract_id', 'is_internal']);
            $table->index(['negotiation_id', 'is_resolved']);
            $table->index(['version_id']);
            $table->index(['parent_id', 'thread_position']);
            $table->index(['requires_response', 'response_due']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_comments');
    }
};
