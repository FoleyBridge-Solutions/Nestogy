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
        Schema::create('physical_mail_postcards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('to_contact_id')->constrained('physical_mail_contacts');
            $table->foreignUuid('from_contact_id')->constrained('physical_mail_contacts');
            $table->foreignUuid('template_id')->nullable()->constrained('physical_mail_templates');
            $table->text('front_content')->nullable();
            $table->text('back_content')->nullable();
            $table->enum('size', ['6x4', '9x6', '11x6'])->default('6x4');
            $table->json('merge_variables')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_mail_postcards');
    }
};
