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
            $table->unsignedBigInteger('company_id');
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
            $table->date('next_maintenance_date')
                ->nullable()

                ->comment('Date when the next scheduled maintenance is due for this asset');
            $table->string('support_level', 50)
                ->nullable()

                ->comment('Level of support: basic, standard, premium, enterprise, etc.');
            $table->boolean('auto_assigned_support')
                ->default(false)

                ->comment('Whether support was automatically assigned vs manually assigned');
            $table->timestamp('support_assigned_at')
                ->nullable()

                ->comment('When support was assigned to this asset');
            $table->unsignedBigInteger('support_assigned_by')
                ->nullable()

                ->comment('User who assigned support to this asset');
            $table->timestamp('support_last_evaluated_at')
                ->nullable()

                ->comment('When support status was last evaluated');
            $table->json('support_evaluation_rules')
                ->nullable()

                ->comment('Rules used to determine support status');
            $table->text('support_notes')
                ->nullable()

                ->comment('Notes about support assignment or exclusion reasons');
            $table->string('asset_tag')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('network_id')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->string('rmm_id')->nullable(); // Remote Monitoring & Management ID

            // Indexes
            $table->index('name');
            $table->index('type');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('status');
            $table->index('ip');
            $table->index('mac');
            $table->index('serial');
            $table->index(['client_id', 'type']);
            $table->index(['client_id', 'status']);
            $table->index('next_maintenance_date');
            $table->enum('support_status', ['supported', 'unsupported', 'pending_assignment', 'excluded'])
                ->default('unsupported')

                ->index()
                ->comment('Whether this asset is covered by a support contract');
            $table->unsignedBigInteger('supporting_contract_id')
                ->nullable()

                ->index()
                ->comment('Contract that provides support for this asset');
            $table->unsignedBigInteger('supporting_schedule_id')
                ->nullable()

                ->index()
                ->comment('Contract schedule (Schedule A) that defines asset support');
            $table->index(['company_id', 'support_status']);
            $table->index(['client_id', 'support_status']);
            $table->index(['supporting_contract_id', 'support_status']);
            $table->index(['support_status', 'type']);
            $table->index(['support_last_evaluated_at']);
            $table->index(['auto_assigned_support']);
            $table->index(['company_id', 'client_id']);
            $table->index('warranty_expire');
            $table->index('archived_at');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('network_id')->references('id')->on('networks')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
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
