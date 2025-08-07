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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->integer('type')->default(1); // 1 = client tag
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            
            // Indexes
            $table->index('name');
            $table->index('type');
            $table->index('company_id');
            $table->index(['company_id', 'type']);
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};