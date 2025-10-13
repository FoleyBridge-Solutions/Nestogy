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
        Schema::table('assets', function (Blueprint $table) {
            // Support status tracking
            $table->enum('support_status', ['supported', 'unsupported', 'pending_assignment', 'excluded'])
                ->default('unsupported')
                ->after('status')
                ->index()
                ->comment('Whether this asset is covered by a support contract');

            // Support level classification
            $table->string('support_level', 50)
                ->nullable()
                ->after('support_status')
                ->comment('Level of support: basic, standard, premium, enterprise, etc.');

            // Contract relationships for support
            $table->unsignedBigInteger('supporting_contract_id')
                ->nullable()
                ->after('support_level')
                ->index()
                ->comment('Contract that provides support for this asset');

            $table->unsignedBigInteger('supporting_schedule_id')
                ->nullable()
                ->after('supporting_contract_id')
                ->index()
                ->comment('Contract schedule (Schedule A) that defines asset support');

            // Support assignment tracking
            $table->boolean('auto_assigned_support')
                ->default(false)
                ->after('supporting_schedule_id')
                ->comment('Whether support was automatically assigned vs manually assigned');

            $table->timestamp('support_assigned_at')
                ->nullable()
                ->after('auto_assigned_support')
                ->comment('When support was assigned to this asset');

            $table->unsignedBigInteger('support_assigned_by')
                ->nullable()
                ->after('support_assigned_at')
                ->comment('User who assigned support to this asset');

            // Support evaluation and review
            $table->timestamp('support_last_evaluated_at')
                ->nullable()
                ->after('support_assigned_by')
                ->comment('When support status was last evaluated');

            $table->json('support_evaluation_rules')
                ->nullable()
                ->after('support_last_evaluated_at')
                ->comment('Rules used to determine support status');

            $table->text('support_notes')
                ->nullable()
                ->after('support_evaluation_rules')
                ->comment('Notes about support assignment or exclusion reasons');

            // Foreign key constraints
            $table->foreign('supporting_contract_id')
                ->references('id')
                ->on('contracts')
                ->onDelete('set null');

            $table->foreign('supporting_schedule_id')
                ->references('id')
                ->on('contract_schedules')
                ->onDelete('set null');

            $table->foreign('support_assigned_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes for performance
            $table->index(['company_id', 'support_status']); // Support status queries by company
            $table->index(['client_id', 'support_status']); // Client support coverage queries
            $table->index(['supporting_contract_id', 'support_status']); // Contract coverage queries
            $table->index(['support_status', 'type']); // Support status by asset type
            $table->index(['support_last_evaluated_at']); // Find assets needing re-evaluation
            $table->index(['auto_assigned_support']); // Track auto vs manual assignments
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['supporting_contract_id']);
            $table->dropForeign(['supporting_schedule_id']);
            $table->dropForeign(['support_assigned_by']);

            // Drop indexes
            $table->dropIndex(['company_id', 'support_status']);
            $table->dropIndex(['client_id', 'support_status']);
            $table->dropIndex(['supporting_contract_id', 'support_status']);
            $table->dropIndex(['support_status', 'type']);
            $table->dropIndex(['support_last_evaluated_at']);
            $table->dropIndex(['auto_assigned_support']);

            // Drop columns
            $table->dropColumn([
                'support_status',
                'support_level',
                'supporting_contract_id',
                'supporting_schedule_id',
                'auto_assigned_support',
                'support_assigned_at',
                'support_assigned_by',
                'support_last_evaluated_at',
                'support_evaluation_rules',
                'support_notes',
            ]);
        });
    }
};
