<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComplianceAuditSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('manage_compliance_settings');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // SOC 2 Compliance
            'soc2_compliance_enabled' => 'boolean',
            'soc2_settings' => 'nullable|array',
            'soc2_settings.security_principle' => 'boolean',
            'soc2_settings.availability_principle' => 'boolean',
            'soc2_settings.processing_integrity_principle' => 'boolean',
            'soc2_settings.confidentiality_principle' => 'boolean',
            'soc2_settings.privacy_principle' => 'boolean',
            'soc2_settings.annual_assessment_required' => 'boolean',
            'soc2_settings.continuous_monitoring' => 'boolean',
            'soc2_settings.evidence_retention_years' => 'integer|min:3|max:10',
            'soc2_settings.access_review_frequency_days' => 'integer|min:30|max:365',
            'soc2_settings.control_testing_frequency_days' => 'integer|min:30|max:180',
            'soc2_settings.incident_response_plan_required' => 'boolean',
            'soc2_settings.vendor_management_required' => 'boolean',
            'soc2_settings.change_management_required' => 'boolean',

            // HIPAA Compliance
            'hipaa_compliance_enabled' => 'boolean',
            'hipaa_settings' => 'nullable|array',
            'hipaa_settings.covered_entity' => 'boolean',
            'hipaa_settings.business_associate' => 'boolean',
            'hipaa_settings.phi_handling_enabled' => 'boolean',
            'hipaa_settings.encryption_required' => 'boolean',
            'hipaa_settings.access_logging_required' => 'boolean',
            'hipaa_settings.minimum_necessary_standard' => 'boolean',
            'hipaa_settings.breach_notification_enabled' => 'boolean',
            'hipaa_settings.breach_notification_days' => 'integer|min:1|max:60',
            'hipaa_settings.risk_assessment_frequency_months' => 'integer|min:6|max:24',
            'hipaa_settings.employee_training_required' => 'boolean',
            'hipaa_settings.employee_training_frequency_months' => 'integer|min:6|max:24',
            'hipaa_settings.baa_tracking_enabled' => 'boolean',
            'hipaa_settings.audit_log_retention_years' => 'integer|min:6|max:10',

            // PCI DSS Compliance
            'pci_compliance_enabled' => 'boolean',
            'pci_settings' => 'nullable|array',
            'pci_settings.merchant_level' => 'string|in:level_1,level_2,level_3,level_4',
            'pci_settings.cardholder_data_environment' => 'boolean',
            'pci_settings.network_segmentation_required' => 'boolean',
            'pci_settings.vulnerability_scans_required' => 'boolean',
            'pci_settings.vulnerability_scan_frequency_days' => 'integer|min:30|max:90',
            'pci_settings.penetration_testing_required' => 'boolean',
            'pci_settings.penetration_testing_frequency_months' => 'integer|min:6|max:12',
            'pci_settings.file_integrity_monitoring' => 'boolean',
            'pci_settings.web_application_firewall' => 'boolean',
            'pci_settings.access_control_required' => 'boolean',
            'pci_settings.encryption_in_transit' => 'boolean',
            'pci_settings.encryption_at_rest' => 'boolean',
            'pci_settings.key_management_required' => 'boolean',

            // GDPR Compliance
            'gdpr_compliance_enabled' => 'boolean',
            'gdpr_settings' => 'nullable|array',
            'gdpr_settings.data_controller' => 'boolean',
            'gdpr_settings.data_processor' => 'boolean',
            'gdpr_settings.eu_operations' => 'boolean',
            'gdpr_settings.consent_management' => 'boolean',
            'gdpr_settings.right_to_be_forgotten' => 'boolean',
            'gdpr_settings.data_portability' => 'boolean',
            'gdpr_settings.privacy_by_design' => 'boolean',
            'gdpr_settings.dpia_required' => 'boolean',
            'gdpr_settings.dpo_appointed' => 'boolean',
            'gdpr_settings.dpo_contact_info' => 'nullable|string|max:255',
            'gdpr_settings.breach_notification_hours' => 'integer|min:1|max:72',
            'gdpr_settings.supervisory_authority' => 'nullable|string|max:255',
            'gdpr_settings.lawful_basis_tracking' => 'boolean',
            'gdpr_settings.cross_border_transfers' => 'boolean',

            // Industry-Specific Compliance
            'industry_compliance_settings' => 'nullable|array',
            'industry_compliance_settings.iso_27001' => 'boolean',
            'industry_compliance_settings.iso_9001' => 'boolean',
            'industry_compliance_settings.cmmc' => 'boolean',
            'industry_compliance_settings.cmmc_level' => 'nullable|integer|min:1|max:3',
            'industry_compliance_settings.nist_cybersecurity_framework' => 'boolean',
            'industry_compliance_settings.fisma' => 'boolean',
            'industry_compliance_settings.fedramp' => 'boolean',
            'industry_compliance_settings.sox' => 'boolean',
            'industry_compliance_settings.coso' => 'boolean',
            'industry_compliance_settings.cobit' => 'boolean',
            'industry_compliance_settings.custom_frameworks' => 'nullable|array',
            'industry_compliance_settings.custom_frameworks.*.name' => 'string|max:100',
            'industry_compliance_settings.custom_frameworks.*.description' => 'nullable|string|max:500',
            'industry_compliance_settings.custom_frameworks.*.enabled' => 'boolean',

            // Data Retention Policies
            'data_retention_policies' => 'nullable|array',
            'data_retention_policies.default_retention_years' => 'integer|min:1|max:50',
            'data_retention_policies.legal_hold_enabled' => 'boolean',
            'data_retention_policies.automated_deletion' => 'boolean',
            'data_retention_policies.deletion_approval_required' => 'boolean',
            'data_retention_policies.retention_schedules' => 'nullable|array',
            'data_retention_policies.retention_schedules.*.data_type' => 'string|max:100',
            'data_retention_policies.retention_schedules.*.retention_period_years' => 'integer|min:1|max:50',
            'data_retention_policies.retention_schedules.*.legal_requirement' => 'boolean',
            'data_retention_policies.retention_schedules.*.business_need' => 'boolean',
            'data_retention_policies.retention_schedules.*.enabled' => 'boolean',

            // Data Destruction Policies
            'data_destruction_policies' => 'nullable|array',
            'data_destruction_policies.secure_deletion_required' => 'boolean',
            'data_destruction_policies.destruction_method' => 'string|in:overwrite,crypto_erase,physical_destruction,degaussing',
            'data_destruction_policies.verification_required' => 'boolean',
            'data_destruction_policies.certificate_of_destruction' => 'boolean',
            'data_destruction_policies.witness_required' => 'boolean',
            'data_destruction_policies.destruction_logs' => 'boolean',
            'data_destruction_policies.destruction_approval_required' => 'boolean',
            'data_destruction_policies.emergency_destruction_procedures' => 'boolean',

            // Risk Assessment Settings
            'risk_assessment_settings' => 'nullable|array',
            'risk_assessment_settings.enabled' => 'boolean',
            'risk_assessment_settings.assessment_frequency_months' => 'integer|min:3|max:24',
            'risk_assessment_settings.risk_scoring_method' => 'string|in:qualitative,quantitative,hybrid',
            'risk_assessment_settings.risk_appetite_defined' => 'boolean',
            'risk_assessment_settings.risk_tolerance_levels' => 'nullable|array',
            'risk_assessment_settings.risk_tolerance_levels.low' => 'integer|min:1|max:100',
            'risk_assessment_settings.risk_tolerance_levels.medium' => 'integer|min:1|max:100',
            'risk_assessment_settings.risk_tolerance_levels.high' => 'integer|min:1|max:100',
            'risk_assessment_settings.third_party_risk_assessment' => 'boolean',
            'risk_assessment_settings.continuous_monitoring' => 'boolean',
            'risk_assessment_settings.risk_register_maintenance' => 'boolean',

            // Vendor Compliance Settings
            'vendor_compliance_settings' => 'nullable|array',
            'vendor_compliance_settings.due_diligence_required' => 'boolean',
            'vendor_compliance_settings.security_questionnaires' => 'boolean',
            'vendor_compliance_settings.soc2_reports_required' => 'boolean',
            'vendor_compliance_settings.insurance_requirements' => 'boolean',
            'vendor_compliance_settings.contract_security_clauses' => 'boolean',
            'vendor_compliance_settings.ongoing_monitoring' => 'boolean',
            'vendor_compliance_settings.performance_reviews' => 'boolean',
            'vendor_compliance_settings.review_frequency_months' => 'integer|min:3|max:24',
            'vendor_compliance_settings.termination_procedures' => 'boolean',
            'vendor_compliance_settings.data_return_requirements' => 'boolean',

            // Incident Response Settings
            'incident_response_settings' => 'nullable|array',
            'incident_response_settings.plan_enabled' => 'boolean',
            'incident_response_settings.response_team_defined' => 'boolean',
            'incident_response_settings.escalation_procedures' => 'boolean',
            'incident_response_settings.communication_plan' => 'boolean',
            'incident_response_settings.external_notifications' => 'boolean',
            'incident_response_settings.law_enforcement_contact' => 'boolean',
            'incident_response_settings.forensic_procedures' => 'boolean',
            'incident_response_settings.recovery_procedures' => 'boolean',
            'incident_response_settings.lessons_learned_process' => 'boolean',
            'incident_response_settings.tabletop_exercises' => 'boolean',
            'incident_response_settings.exercise_frequency_months' => 'integer|min:3|max:12',
            'incident_response_settings.incident_classification' => 'nullable|array',
            'incident_response_settings.incident_classification.*.severity' => 'string|in:low,medium,high,critical',
            'incident_response_settings.incident_classification.*.response_time_hours' => 'integer|min:1|max:168',
            'incident_response_settings.incident_classification.*.notification_required' => 'boolean',

            // Audit Logging Enhanced Settings
            'audit_logging_enabled' => 'boolean',
            'audit_retention_days' => 'integer|min:90|max:2555',
            'audit_settings' => 'nullable|array',
            'audit_settings.log_user_access' => 'boolean',
            'audit_settings.log_data_access' => 'boolean',
            'audit_settings.log_configuration_changes' => 'boolean',
            'audit_settings.log_privileged_operations' => 'boolean',
            'audit_settings.log_failed_attempts' => 'boolean',
            'audit_settings.log_file_access' => 'boolean',
            'audit_settings.log_network_connections' => 'boolean',
            'audit_settings.real_time_monitoring' => 'boolean',
            'audit_settings.anomaly_detection' => 'boolean',
            'audit_settings.siem_integration' => 'boolean',
            'audit_settings.log_forwarding_enabled' => 'boolean',
            'audit_settings.log_encryption' => 'boolean',
            'audit_settings.log_integrity_monitoring' => 'boolean',

            // Training and Awareness
            'training_settings' => 'nullable|array',
            'training_settings.security_awareness_required' => 'boolean',
            'training_settings.compliance_training_required' => 'boolean',
            'training_settings.role_specific_training' => 'boolean',
            'training_settings.training_frequency_months' => 'integer|min:3|max:24',
            'training_settings.training_completion_tracking' => 'boolean',
            'training_settings.phishing_simulations' => 'boolean',
            'training_settings.phishing_frequency_months' => 'integer|min:1|max:12',
            'training_settings.training_documentation' => 'boolean',
            'training_settings.certification_requirements' => 'boolean',

            // Documentation and Policies
            'documentation_settings' => 'nullable|array',
            'documentation_settings.policy_management' => 'boolean',
            'documentation_settings.procedure_documentation' => 'boolean',
            'documentation_settings.version_control' => 'boolean',
            'documentation_settings.approval_workflows' => 'boolean',
            'documentation_settings.review_frequency_months' => 'integer|min:6|max:24',
            'documentation_settings.acknowledgment_tracking' => 'boolean',
            'documentation_settings.change_notifications' => 'boolean',
            'documentation_settings.document_retention' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'soc2_settings.evidence_retention_years.min' => 'SOC 2 evidence must be retained for at least 3 years.',
            'hipaa_settings.breach_notification_days.max' => 'HIPAA breach notification must occur within 60 days.',
            'gdpr_settings.breach_notification_hours.max' => 'GDPR breach notification must occur within 72 hours.',
            'pci_settings.vulnerability_scan_frequency_days.max' => 'PCI DSS requires vulnerability scans at least quarterly.',
            'data_retention_policies.default_retention_years.min' => 'Minimum retention period is 1 year.',
            'risk_assessment_settings.assessment_frequency_months.min' => 'Risk assessments must be conducted at least quarterly.',
            'vendor_compliance_settings.review_frequency_months.min' => 'Vendor reviews must be conducted at least quarterly.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'soc2_compliance_enabled' => 'SOC 2 compliance',
            'hipaa_compliance_enabled' => 'HIPAA compliance',
            'pci_compliance_enabled' => 'PCI DSS compliance',
            'gdpr_compliance_enabled' => 'GDPR compliance',
            'audit_logging_enabled' => 'audit logging',
            'soc2_settings.continuous_monitoring' => 'continuous monitoring',
            'hipaa_settings.phi_handling_enabled' => 'PHI handling',
            'pci_settings.cardholder_data_environment' => 'cardholder data environment',
            'gdpr_settings.consent_management' => 'consent management',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate risk tolerance levels sum to 100%
            $riskSettings = $this->input('risk_assessment_settings', []);
            if (isset($riskSettings['risk_tolerance_levels'])) {
                $levels = $riskSettings['risk_tolerance_levels'];
                if (isset($levels['low'], $levels['medium'], $levels['high'])) {
                    $total = $levels['low'] + $levels['medium'] + $levels['high'];
                    if ($total !== 100) {
                        $validator->errors()->add(
                            'risk_assessment_settings.risk_tolerance_levels',
                            'Risk tolerance levels must sum to 100%.'
                        );
                    }
                }
            }

            // Validate CMMC level requirements
            $industrySettings = $this->input('industry_compliance_settings', []);
            if (isset($industrySettings['cmmc']) && $industrySettings['cmmc'] === true) {
                if (! isset($industrySettings['cmmc_level']) || $industrySettings['cmmc_level'] < 1) {
                    $validator->errors()->add(
                        'industry_compliance_settings.cmmc_level',
                        'CMMC level is required when CMMC compliance is enabled.'
                    );
                }
            }

            // Validate incident response timing sequence
            $incidentSettings = $this->input('incident_response_settings', []);
            if (isset($incidentSettings['incident_classification'])) {
                $classifications = $incidentSettings['incident_classification'];
                $severityOrder = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];

                foreach ($classifications as $index => $classification) {
                    if (isset($classification['severity'], $classification['response_time_hours'])) {
                        // Higher severity should have lower response times
                        foreach ($classifications as $otherIndex => $otherClassification) {
                            if ($index !== $otherIndex &&
                                isset($otherClassification['severity'], $otherClassification['response_time_hours'])) {

                                $currentSeverityLevel = $severityOrder[$classification['severity']] ?? 0;
                                $otherSeverityLevel = $severityOrder[$otherClassification['severity']] ?? 0;

                                if ($currentSeverityLevel > $otherSeverityLevel &&
                                    $classification['response_time_hours'] >= $otherClassification['response_time_hours']) {
                                    $validator->errors()->add(
                                        "incident_response_settings.incident_classification.{$index}.response_time_hours",
                                        'Higher severity incidents must have shorter response times.'
                                    );
                                }
                            }
                        }
                    }
                }
            }

            // Validate training frequency alignment
            $trainingSettings = $this->input('training_settings', []);
            if (isset($trainingSettings['phishing_frequency_months'], $trainingSettings['training_frequency_months'])) {
                $phishingFreq = $trainingSettings['phishing_frequency_months'];
                $trainingFreq = $trainingSettings['training_frequency_months'];

                if ($phishingFreq > $trainingFreq) {
                    $validator->errors()->add(
                        'training_settings.phishing_frequency_months',
                        'Phishing simulations should occur more frequently than general training.'
                    );
                }
            }
        });
    }
}
