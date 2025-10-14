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
        Schema::create('client_services', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->constrained()->onDelete('cascade');
                        $table->string('name');
                        $table->text('description')->nullable();
                        $table->enum('type', ['managed', 'monitoring', 'backup', 'security', 'support', 'other'])->default('other');
                        $table->decimal('monthly_rate', 10, 2)->default(0);
                        $table->date('start_date');
                        $table->date('end_date')->nullable();
                        $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
                        $table->json('configuration')->nullable();
                        $table->timestamps();

                        $table->index(['company_id', 'client_id']);
                        $table->index(['company_id', 'type']);
                        $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_services');
    }
};
