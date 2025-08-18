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
        Schema::create('quote_versions', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('quote_id')->constrained('quotes')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Version tracking
            $table->integer('version_number');
            
            // Data storage
            $table->json('quote_data'); // Complete quote snapshot
            $table->json('changes')->nullable(); // What changed from previous version
            $table->string('change_reason', 500)->nullable(); // Why the change was made
            
            // Timestamps and soft deletes
            $table->timestamps();
            $table->timestamp('archived_at')->nullable(); // Soft delete column
            
            // Indexes for performance
            $table->index(['quote_id', 'version_number']);
            $table->index(['company_id', 'created_at']);
            $table->index('created_by');
            
            // Unique constraint to prevent duplicate version numbers per quote
            $table->unique(['quote_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_versions');
    }
};
