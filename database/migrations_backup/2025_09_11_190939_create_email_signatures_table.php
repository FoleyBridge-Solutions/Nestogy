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
        Schema::create('email_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_account_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('name'); // "Default", "Business", "Personal"
            $table->longText('content_html')->nullable();
            $table->longText('content_text')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('auto_append_replies')->default(true);
            $table->boolean('auto_append_forwards')->default(true);
            $table->json('conditions')->nullable(); // Rules for when to use this signature

            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['email_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_signatures');
    }
};
