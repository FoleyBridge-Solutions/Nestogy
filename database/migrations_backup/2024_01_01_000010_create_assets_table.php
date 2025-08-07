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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // Server, Workstation, Laptop, etc.
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('make');
            $table->string('model')->nullable();
            $table->string('serial')->nullable();
            $table->string('os')->nullable();
            $table->string('ip', 45)->nullable(); // Support IPv6
            $table->string('nat_ip')->nullable();
            $table->string('mac', 17)->nullable();
            $table->string('uri', 500)->nullable();
            $table->string('uri_2', 500)->nullable();
            $table->string('status')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expire')->nullable();
            $table->date('install_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('network_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('rmm_id')->nullable(); // Remote Monitoring & Management ID

            // Indexes
            $table->index('name');
            $table->index('type');
            $table->index('client_id');
            $table->index('status');
            $table->index('ip');
            $table->index('mac');
            $table->index('serial');
            $table->index(['client_id', 'type']);
            $table->index(['client_id', 'status']);
            $table->index('warranty_expire');
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};