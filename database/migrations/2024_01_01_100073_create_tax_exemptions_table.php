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
        Schema::create('tax_exemptions', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->string('name');
                        $table->timestamps();
                        $table->integer('priority')->default(0);
                        $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                        $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                        $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_exemptions');
    }
};
