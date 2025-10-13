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
        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->constrained()->onDelete('cascade');
                        $table->string('name');
                        $table->string('title')->nullable();
                        $table->string('email')->nullable();
                        $table->string('phone')->nullable();
                        $table->string('mobile')->nullable();
                        $table->boolean('is_primary')->default(false);
                        $table->boolean('is_billing')->default(false);
                        $table->boolean('is_technical')->default(false);
                        $table->text('notes')->nullable();
                        $table->timestamps();

                        $table->index(['company_id', 'client_id']);
                        $table->index(['company_id', 'email']);
                        $table->index(['company_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_contacts');
    }
};
