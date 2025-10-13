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
        Schema::create('company_hierarchies', function (Blueprint $table) {
            $table->id();

            // Hierarchy relationship fields
            $table->unsignedBigInteger('ancestor_id')->index();  // The parent/ancestor company
            $table->unsignedBigInteger('descendant_id')->index(); // The child/descendant company
            $table->unsignedInteger('depth')->default(1); // How many levels deep (1 = direct child)

            // Path information for efficient queries
            $table->string('path', 1000)->nullable(); // Full path from root: /1/5/12/
            $table->text('path_names')->nullable(); // Human readable path: "Acme Corp / IT Division / Web Team"

            // Relationship metadata
            $table->enum('relationship_type', ['parent_child', 'division', 'branch', 'subsidiary'])->default('subsidiary');
            $table->json('relationship_metadata')->nullable(); // Additional relationship data

            // Timestamps
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('ancestor_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('descendant_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            // Unique constraint to prevent duplicate relationships
            $table->unique(['ancestor_id', 'descendant_id'], 'unique_hierarchy_relationship');

            // Composite indexes for common queries
            $table->index(['ancestor_id', 'depth'], 'hierarchy_ancestor_depth_idx');
            $table->index(['descendant_id', 'depth'], 'hierarchy_descendant_depth_idx');
            $table->index(['path'], 'hierarchy_path_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_hierarchies');
    }
};
