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
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->string('status')->default('active');
                        $table->decimal('amount', 15, 2)->default(0);
                        $table->timestamps();
                        $table->string('request_number')->nullable();
                        $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
