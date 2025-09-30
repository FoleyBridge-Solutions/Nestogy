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
        Schema::create('contract_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->integer('amendment_number');
            $table->enum('amendment_type', ['renewal', 'pricing', 'term', 'sla', 'scope', 'general']);
            $table->json('changes'); // Array of changes
            $table->json('original_values')->nullable(); // Array of original values
            $table->text('reason'); // Reason for amendment
            $table->date('effective_date'); // When amendment takes effect
            $table->enum('status', ['pending', 'approved', 'applied', 'rejected'])->default('pending');
            $table->timestamp('applied_at')->nullable(); // When amendment was applied
            $table->foreignId('applied_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('approval_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['contract_id', 'amendment_number']);
            $table->index(['company_id', 'status']);
            $table->index('effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_amendments');
    }
};
