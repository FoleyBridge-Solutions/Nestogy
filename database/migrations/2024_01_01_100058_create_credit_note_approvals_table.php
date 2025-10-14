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
        Schema::create('credit_note_approvals', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->string('status')->default('active');
                        $table->timestamps();
                        $table->timestamp('requested_at')->nullable();
                        $table->timestamp('reviewed_at')->nullable();
                        $table->timestamp('approved_at')->nullable();
                        $table->timestamp('rejected_at')->nullable();
                        $table->timestamp('expired_at')->nullable();
                        $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_note_approvals');
    }
};
