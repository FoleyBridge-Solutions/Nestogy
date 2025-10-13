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
        Schema::create('ip_lookup_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45)->index(); // IPv6 support
            $table->string('country', 100)->nullable();
            $table->string('country_code', 2)->nullable()->index();
            $table->string('region', 100)->nullable();
            $table->string('region_code', 10)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('zip', 20)->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('timezone', 50)->nullable();
            $table->string('isp', 255)->nullable();
            $table->boolean('is_valid')->default(true);
            $table->boolean('is_vpn')->default(false)->index();
            $table->boolean('is_proxy')->default(false)->index();
            $table->boolean('is_tor')->default(false)->index();
            $table->enum('threat_level', ['low', 'medium', 'high', 'critical'])->default('low')->index();
            $table->enum('lookup_source', ['api_ninjas', 'ipapi', 'maxmind'])->default('api_ninjas');
            $table->json('api_response')->nullable();
            $table->timestamp('cached_until')->nullable()->index();
            $table->unsignedInteger('lookup_count')->default(1);
            $table->timestamp('last_lookup_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->unique(['ip_address', 'company_id']);
            $table->index(['company_id', 'threat_level']);
            $table->index(['is_vpn', 'is_proxy', 'is_tor']);
            $table->index(['country_code', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_lookup_logs');
    }
};
