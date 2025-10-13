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
        Schema::create('client_credentials', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->constrained()->onDelete('cascade');
                        $table->string('service_name');
                        $table->string('username');
                        $table->text('password');
                        $table->string('url')->nullable();
                        $table->text('notes')->nullable();
                        $table->json('additional_fields')->nullable();
                        $table->boolean('is_shared')->default(false);
                        $table->timestamps();

                        $table->index(['company_id', 'client_id']);
                        $table->index(['company_id', 'service_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_credentials');
    }
};
