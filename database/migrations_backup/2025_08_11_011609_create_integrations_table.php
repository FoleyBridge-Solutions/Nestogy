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
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // connectwise, datto, ninja, generic
            $table->string('name');
            $table->string('api_endpoint')->nullable();
            $table->string('webhook_url')->nullable();
            $table->text('credentials_encrypted'); // encrypted JSON
            $table->json('field_mappings')->nullable(); // field mapping config
            $table->json('alert_rules')->nullable(); // alert processing rules
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'provider']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
