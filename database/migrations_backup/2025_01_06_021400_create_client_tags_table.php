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
        Schema::create('client_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('tag_id');
            
            // Composite primary key
            $table->primary(['client_id', 'tag_id']);
            
            // Foreign keys
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
            
            // Indexes
            $table->index('client_id');
            $table->index('tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_tags');
    }
};