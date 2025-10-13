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
        Schema::create('client_it_documentation', function (Blueprint $table) {
            $table->id();
                        $table->foreignId('company_id')->constrained()->onDelete('cascade');
                        $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
                        $table->foreignId('authored_by')->nullable()->constrained('users')->onDelete('set null');
                        $table->string('name');
                        $table->text('description')->nullable();
                        $table->string('it_category')->nullable();
                        $table->json('system_references')->nullable();
                        $table->json('ip_addresses')->nullable();
                        $table->json('software_versions')->nullable();
                        $table->json('compliance_requirements')->nullable();
                        $table->string('review_schedule')->nullable();
                        $table->timestamp('last_reviewed_at')->nullable();
                        $table->timestamp('next_review_at')->nullable();
                        $table->string('access_level')->nullable();
                        $table->json('procedure_steps')->nullable();
                        $table->json('network_diagram')->nullable();
                        $table->json('related_entities')->nullable();
                        $table->json('tags')->nullable();
                        $table->integer('version')->default(1);
                        $table->foreignId('parent_document_id')->nullable()->constrained('client_it_documentation')->onDelete('cascade');
                        $table->string('file_path')->nullable();
                        $table->string('original_filename')->nullable();
                        $table->string('filename')->nullable();
                        $table->bigInteger('file_size')->nullable();
                        $table->string('mime_type')->nullable();
                        $table->string('file_hash')->nullable();
                        $table->timestamp('last_accessed_at')->nullable();
                        $table->integer('access_count')->default(0);
                        $table->boolean('is_active')->default(true);
                        $table->json('enabled_tabs')->nullable();
                        $table->json('tab_configuration')->nullable();
                        $table->string('status')->default('active');
                        $table->date('effective_date')->nullable();
                        $table->date('expiry_date')->nullable();
                        $table->string('template_used')->nullable();
                        $table->json('ports')->nullable();
                        $table->json('api_endpoints')->nullable();
                        $table->json('ssl_certificates')->nullable();
                        $table->json('dns_entries')->nullable();
                        $table->json('firewall_rules')->nullable();
                        $table->json('vpn_settings')->nullable();
                        $table->json('hardware_references')->nullable();
                        $table->json('environment_variables')->nullable();
                        $table->json('procedure_diagram')->nullable();
                        $table->json('rollback_procedures')->nullable();
                        $table->json('prerequisites')->nullable();
                        $table->string('data_classification')->nullable();
                        $table->boolean('encryption_required')->default(false);
                        $table->json('audit_requirements')->nullable();
                        $table->json('security_controls')->nullable();
                        $table->json('external_resources')->nullable();
                        $table->json('vendor_contacts')->nullable();
                        $table->json('support_contracts')->nullable();
                        $table->json('test_cases')->nullable();
                        $table->json('validation_checklist')->nullable();
                        $table->json('performance_benchmarks')->nullable();
                        $table->json('health_checks')->nullable();
                        $table->json('automation_scripts')->nullable();
                        $table->json('integrations')->nullable();
                        $table->json('webhooks')->nullable();
                        $table->json('scheduled_tasks')->nullable();
                        $table->decimal('uptime_requirement', 5, 2)->nullable();
                        $table->integer('rto')->nullable(); // Recovery Time Objective in minutes
                        $table->integer('rpo')->nullable(); // Recovery Point Objective in minutes
                        $table->json('performance_metrics')->nullable();
                        $table->json('alert_thresholds')->nullable();
                        $table->json('escalation_paths')->nullable();
                        $table->text('change_summary')->nullable();
                        $table->json('change_log')->nullable();
                        $table->boolean('requires_technical_review')->default(false);
                        $table->boolean('requires_management_approval')->default(false);
                        $table->json('approval_history')->nullable();
                        $table->json('review_comments')->nullable();
                        $table->json('custom_fields')->nullable();
                        $table->integer('documentation_completeness')->default(0);
                        $table->boolean('is_template')->default(false);
                        $table->string('template_category')->nullable();
                        $table->timestamps();
                        $table->softDeletes();

                        $table->index(['company_id', 'client_id']);
                        $table->index(['company_id', 'it_category']);
                        $table->index(['company_id', 'status']);
                        $table->index(['company_id', 'is_active']);
                        $table->index(['company_id', 'is_template']);
                        $table->index('authored_by');
                        $table->index('parent_document_id');
                        $table->index('last_reviewed_at');
                        $table->index('next_review_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_it_documentation');
    }
};
