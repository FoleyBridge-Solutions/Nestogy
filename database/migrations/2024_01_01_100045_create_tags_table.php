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
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->string('name');
                        $table->integer('type')->default(1); // 1=Client, 2=Ticket, 3=Asset, 4=Document
                        $table->string('color', 7)->nullable();
                        $table->string('icon', 50)->nullable();
                        $table->text('description')->nullable();
                        $table->timestamps();
                        $table->timestamp('archived_at')->nullable();

                        $table->unique(['company_id', 'name']);
                        $table->index(['company_id', 'name']);
                        $table->index('type');
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
