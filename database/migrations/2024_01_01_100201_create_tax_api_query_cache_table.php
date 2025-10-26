<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_api_query_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('api_provider');
            $table->string('query_type');
            $table->string('query_hash');
            $table->json('query_parameters');
            $table->json('api_response');
            $table->timestamp('api_called_at');
            $table->timestamp('expires_at');
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['query_hash', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_api_query_cache');
    }
};
