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
        Schema::create('networks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('vlan')->nullable();
            $table->string('network'); // CIDR notation
            $table->string('gateway');
            $table->string('dhcp_range')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');

            // Indexes
            $table->index('name');
            $table->index('client_id');
            $table->index('location_id');
            $table->index('vlan');
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('networks');
    }
};