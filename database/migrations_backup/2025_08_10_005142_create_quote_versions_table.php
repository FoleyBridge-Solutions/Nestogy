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
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('quote_id');
            $table->integer('version_number');
            $table->json('quote_data'); // Snapshot of quote data at this version
            $table->json('changes')->nullable(); // Track what changed from previous version
            $table->string('change_reason', 500)->nullable(); // Reason for the version change
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for performance
            $table->index(['company_id', 'quote_id']);
            $table->index(['quote_id', 'version_number']);
            $table->index('created_by');
            $table->index('created_at');

            // Unique constraint to prevent duplicate version numbers for same quote
            $table->unique(['quote_id', 'version_number'], 'unique_quote_version');
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
