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
        Schema::create('client_vendors', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->constrained()->onDelete('cascade');
                        $table->string('name');
                        $table->string('contact_person')->nullable();
                        $table->string('email')->nullable();
                        $table->string('phone')->nullable();
                        $table->text('address')->nullable();
                        $table->string('account_number')->nullable();
                        $table->text('notes')->nullable();
                        $table->enum('relationship', ['vendor', 'supplier', 'partner', 'contractor'])->default('vendor');
                        $table->timestamps();

                        $table->index(['company_id', 'client_id']);
                        $table->index(['company_id', 'relationship']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_vendors');
    }
};
