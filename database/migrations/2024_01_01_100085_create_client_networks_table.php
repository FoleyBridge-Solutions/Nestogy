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
        Schema::create('client_networks', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->constrained()->onDelete('cascade');
                        $table->string('name');
                        $table->string('network_address');
                        $table->string('subnet_mask');
                        $table->string('gateway')->nullable();
                        $table->string('dns_primary')->nullable();
                        $table->string('dns_secondary')->nullable();
                        $table->integer('vlan_id')->nullable();
                        $table->text('description')->nullable();
                        $table->enum('type', ['lan', 'wan', 'dmz', 'guest', 'management'])->default('lan');
                        $table->timestamps();

                        $table->index(['company_id', 'client_id']);
                        $table->index(['company_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_networks');
    }
};
