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
        Schema::create('rmm_alerts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('integration_id')->constrained()->onDelete('cascade');
            $table->string('external_alert_id')->index();
            $table->string('device_id')->nullable();
            $table->foreignId('asset_id')->nullable()->constrained()->onDelete('set null');
            $table->string('alert_type');
            $table->string('severity'); // critical, high, medium, low
            $table->text('message');
            $table->json('raw_payload');
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('ticket_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_duplicate')->default(false);
            $table->string('duplicate_hash')->nullable()->index();
            $table->timestamps();
            
            $table->index(['integration_id', 'external_alert_id']);
            $table->index(['severity', 'processed_at']);
            $table->index('is_duplicate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rmm_alerts');
    }
};
