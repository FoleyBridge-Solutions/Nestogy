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
        Schema::create('physical_mail_return_envelopes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('postgrid_id')->nullable()->unique();
            $table->foreignUuid('contact_id')->constrained('physical_mail_contacts');
            $table->integer('quantity_ordered')->default(0);
            $table->integer('quantity_available')->default(0);
            $table->timestamps();

            $table->index('contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_mail_return_envelopes');
    }
};
