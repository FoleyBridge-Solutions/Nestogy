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
        if (! Schema::hasTable('state_tax_rates')) {
            Schema::create('state_tax_rates', function (Blueprint $table) {
                $table->id();
                $table->string('state_code', 2)->unique();
                $table->string('state_name');
                $table->decimal('tax_rate', 5, 3);
                $table->boolean('is_active')->default(true);
                $table->date('effective_date')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index('state_code');
                $table->index('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('state_tax_rates');
    }
};
