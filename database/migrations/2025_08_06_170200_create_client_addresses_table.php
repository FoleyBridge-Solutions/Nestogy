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
        Schema::create('client_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('zip_code');
            $table->string('country')->default('US');
            $table->string('phone')->nullable();
            $table->string('hours')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('accessed_at')->nullable();

            // Indexes
            $table->index('tenant_id');
            $table->index('client_id');
            $table->index('contact_id');
            $table->index('name');
            $table->index('city');
            $table->index('state');
            $table->index('zip_code');
            $table->index('country');
            $table->index('primary');
            $table->index(['tenant_id', 'client_id']);
            $table->index(['client_id', 'primary']);
            $table->index(['state', 'city']);
            $table->index('deleted_at');
            $table->index('accessed_at');

            // Foreign key constraints
            $table->foreign('tenant_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('client_contacts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_addresses');
    }
};