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
        Schema::create('client_racks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('rack_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location');
            $table->string('building')->nullable();
            $table->string('floor')->nullable();
            $table->string('room')->nullable();
            $table->integer('units_total')->default(42);
            $table->integer('units_used')->default(0);
            $table->decimal('power_capacity_watts', 10, 2)->default(0);
            $table->decimal('power_usage_watts', 10, 2)->default(0);
            $table->decimal('temperature_celsius', 5, 2)->nullable();
            $table->decimal('humidity_percent', 5, 2)->nullable();
            $table->enum('security_level', ['low', 'medium', 'high', 'restricted'])->default('medium');
            $table->enum('access_type', ['card', 'biometric', 'key', 'combination'])->default('card');
            $table->enum('status', ['active', 'maintenance', 'decommissioned', 'planned'])->default('active');
            $table->json('equipment_list')->nullable();
            $table->json('access_log')->nullable();
            $table->decimal('monthly_cost', 10, 2)->default(0);
            $table->string('vendor')->nullable();
            $table->string('model')->nullable();
            $table->date('installation_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->date('last_maintenance')->nullable();
            $table->date('next_maintenance')->nullable();
            $table->text('maintenance_notes')->nullable();
            $table->json('alerts')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'client_id']);
            $table->index(['status', 'company_id']);
            $table->index('rack_number');
            $table->index('location');
            $table->index('security_level');
            $table->index('next_maintenance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_racks');
    }
};