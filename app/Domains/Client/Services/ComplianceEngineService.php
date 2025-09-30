<?php

namespace App\Domains\Client\Services;

use App\Domains\Client\Models\ClientITDocumentation;

class ComplianceEngineService
{
    /**
     * Get all available compliance frameworks with detailed requirements
     */
    public function getComplianceFrameworks(): array
    {
        return [
            'gdpr' => [
                'name' => 'GDPR (General Data Protection Regulation)',
                'description' => 'EU data protection and privacy regulation',
                'version' => '2016/679',
                'categories' => [
                    'data_governance' => [
                        'name' => 'Data Governance',
                        'requirements' => [
                            'data_inventory' => 'Maintain comprehensive inventory of all personal data processing activities',
                            'ropa' => 'Records of Processing Activities (Article 30)',
                            'legal_basis' => 'Document legal basis for each processing activity',
                            'privacy_policy' => 'Comprehensive privacy policy with clear data processing information',
                            'data_minimization' => 'Process only necessary data for specified purposes',
                            'purpose_limitation' => 'Use data only for stated purposes',
                        ],
                    ],
                    'data_subject_rights' => [
                        'name' => 'Data Subject Rights',
                        'requirements' => [
                            'access_requests' => 'Process data subject access requests within 30 days',
                            'right_to_erasure' => 'Right to be forgotten procedures',
                            'data_portability' => 'Provide data in portable format',
                            'rectification' => 'Process correction requests promptly',
                            'objection_handling' => 'Handle objections to processing',
                            'automated_decisions' => 'Rights regarding automated decision-making',
                        ],
                    ],
                    'security_measures' => [
                        'name' => 'Technical & Organizational Measures',
                        'requirements' => [
                            'encryption' => 'Encryption of personal data at rest and in transit',
                            'pseudonymization' => 'Pseudonymization where appropriate',
                            'access_controls' => 'Role-based access control implementation',
                            'data_integrity' => 'Ensure ongoing confidentiality, integrity, availability',
                            'security_testing' => 'Regular testing of security measures',
                            'employee_training' => 'Data protection training for all staff',
                        ],
                    ],
                    'breach_management' => [
                        'name' => 'Data Breach Management',
                        'requirements' => [
                            'breach_detection' => 'Systems to detect breaches promptly',
                            '72_hour_notification' => 'Notify supervisory authority within 72 hours',
                            'breach_documentation' => 'Document all breaches, even if not notified',
                            'impact_assessment' => 'Assess risk to individuals\' rights and freedoms',
                            'individual_notification' => 'Notify affected individuals without undue delay',
                        ],
                    ],
                    'accountability' => [
                        'name' => 'Accountability & Governance',
                        'requirements' => [
                            'dpia' => 'Data Protection Impact Assessments for high-risk processing',
                            'dpo_requirement' => 'Data Protection Officer appointment (if required)',
                            'processor_agreements' => 'Data Processing Agreements with all processors',
                            'international_transfers' => 'Appropriate safeguards for international transfers',
                            'privacy_by_design' => 'Privacy by design and by default',
                            'compliance_monitoring' => 'Regular compliance reviews and audits',
                        ],
                    ],
                ],
            ],

            'hipaa' => [
                'name' => 'HIPAA (Health Insurance Portability and Accountability Act)',
                'description' => 'US healthcare data protection standards',
                'version' => 'HIPAA/HITECH 2024',
                'categories' => [
                    'administrative_safeguards' => [
                        'name' => 'Administrative Safeguards',
                        'requirements' => [
                            'security_officer' => 'Designate HIPAA Security Officer',
                            'workforce_training' => 'Regular workforce security training',
                            'access_management' => 'Workforce access authorization procedures',
                            'access_termination' => 'Termination procedures for access',
                            'risk_assessment' => 'Conduct regular risk assessments',
                            'risk_management' => 'Implement risk management process',
                            'sanction_policy' => 'Workforce sanction policy for violations',
                            'information_system_review' => 'Regular information system activity review',
                        ],
                    ],
                    'physical_safeguards' => [
                        'name' => 'Physical Safeguards',
                        'requirements' => [
                            'facility_access' => 'Facility access controls and validation',
                            'workstation_use' => 'Workstation use policies',
                            'workstation_security' => 'Physical workstation security',
                            'device_controls' => 'Device and media controls',
                            'disposal_procedures' => 'Hardware and media disposal procedures',
                            'media_reuse' => 'Media re-use procedures',
                            'data_backup' => 'Data backup and storage procedures',
                        ],
                    ],
                    'technical_safeguards' => [
                        'name' => 'Technical Safeguards',
                        'requirements' => [
                            'unique_user_id' => 'Unique user identification for each user',
                            'automatic_logoff' => 'Automatic logoff implementation',
                            'encryption_decryption' => 'Encryption and decryption capabilities',
                            'audit_logs' => 'Hardware, software, procedural audit logs',
                            'integrity_controls' => 'Electronic PHI integrity controls',
                            'transmission_security' => 'PHI transmission security',
                            'access_controls' => 'Technical access control implementation',
                        ],
                    ],
                    'organizational_requirements' => [
                        'name' => 'Organizational Requirements',
                        'requirements' => [
                            'business_associate_agreements' => 'BAAs with all business associates',
                            'subcontractor_agreements' => 'Subcontractor compliance assurance',
                            'plan_documents' => 'Written security plan documentation',
                            'incident_response' => 'Security incident response procedures',
                            'contingency_plan' => 'Data backup and disaster recovery plan',
                            'evaluation' => 'Periodic technical and non-technical evaluation',
                        ],
                    ],
                    'breach_notification' => [
                        'name' => 'Breach Notification Rule',
                        'requirements' => [
                            'risk_assessment' => 'Breach risk assessment procedures',
                            'individual_notice' => 'Individual notification within 60 days',
                            'media_notice' => 'Media notification for large breaches',
                            'hhs_notification' => 'HHS notification requirements',
                            'breach_log' => 'Breach documentation and logging',
                            'annual_summary' => 'Annual breach summary to HHS',
                        ],
                    ],
                ],
            ],

            'soc2' => [
                'name' => 'SOC 2 Type II',
                'description' => 'Service Organization Control 2 - Trust Service Criteria',
                'version' => '2017 TSC (2022 Update)',
                'categories' => [
                    'security' => [
                        'name' => 'Security (CC - Common Criteria)',
                        'requirements' => [
                            'cc1_control_environment' => 'Control environment and organizational structure',
                            'cc2_communication' => 'Information and communication systems',
                            'cc3_risk_assessment' => 'Risk assessment process',
                            'cc4_monitoring' => 'Monitoring of controls',
                            'cc5_control_activities' => 'Control activities selection and development',
                            'cc6_logical_access' => 'Logical and physical access controls',
                            'cc7_system_operations' => 'System operations',
                            'cc8_change_management' => 'Change management',
                            'cc9_risk_mitigation' => 'Risk mitigation activities',
                        ],
                    ],
                    'availability' => [
                        'name' => 'Availability (A - Additional Criteria)',
                        'requirements' => [
                            'a1_capacity_planning' => 'Capacity planning and monitoring',
                            'a1_environmental_protection' => 'Environmental protection from disruptions',
                            'a1_recovery_capabilities' => 'Recovery capabilities and procedures',
                            'a1_incident_handling' => 'Incident handling and resolution',
                            'a1_performance_monitoring' => 'System performance monitoring',
                        ],
                    ],
                    'processing_integrity' => [
                        'name' => 'Processing Integrity (PI)',
                        'requirements' => [
                            'pi1_quality_assurance' => 'Processing quality assurance',
                            'pi1_system_inputs' => 'Complete and accurate system inputs',
                            'pi1_data_processing' => 'Data processing accuracy',
                            'pi1_output_completeness' => 'Output completeness and accuracy',
                            'pi1_error_handling' => 'Processing error identification and correction',
                        ],
                    ],
                    'confidentiality' => [
                        'name' => 'Confidentiality (C)',
                        'requirements' => [
                            'c1_data_classification' => 'Data classification and handling',
                            'c1_data_retention' => 'Data retention and disposal',
                            'c1_data_access' => 'Confidential data access restrictions',
                            'c1_data_disclosure' => 'Data disclosure and dissemination controls',
                            'c1_data_destruction' => 'Secure data destruction procedures',
                        ],
                    ],
                    'privacy' => [
                        'name' => 'Privacy (P)',
                        'requirements' => [
                            'p1_notice' => 'Privacy notice and communication',
                            'p2_choice_consent' => 'Choice and consent mechanisms',
                            'p3_collection' => 'Personal information collection limits',
                            'p4_use_retention' => 'Use, retention, and disposal policies',
                            'p5_access' => 'Access to personal information',
                            'p6_disclosure' => 'Disclosure to third parties',
                            'p7_quality' => 'Personal information quality and accuracy',
                            'p8_monitoring' => 'Privacy monitoring and compliance',
                        ],
                    ],
                ],
            ],

            'pci_dss' => [
                'name' => 'PCI DSS 4.0',
                'description' => 'Payment Card Industry Data Security Standard',
                'version' => '4.0 (March 2024)',
                'categories' => [
                    'network_security' => [
                        'name' => 'Build and Maintain Secure Networks',
                        'requirements' => [
                            'req1_network_controls' => 'Install and maintain network security controls',
                            'req1_1_network_diagram' => 'Current network diagram with all CHD flows',
                            'req1_2_firewall_config' => 'Firewall configuration standards',
                            'req1_3_dmz' => 'DMZ implementation for public services',
                            'req2_secure_config' => 'Apply secure configurations to all system components',
                            'req2_1_inventory' => 'Maintain inventory of system components',
                            'req2_2_vendor_defaults' => 'Change all vendor default passwords',
                        ],
                    ],
                    'data_protection' => [
                        'name' => 'Protect Cardholder Data',
                        'requirements' => [
                            'req3_protect_stored' => 'Protect stored account data',
                            'req3_1_retention' => 'Data retention and disposal policies',
                            'req3_2_sad_storage' => 'Do not store sensitive authentication data',
                            'req3_4_pan_masking' => 'PAN masking when displayed',
                            'req3_5_encryption' => 'Secure cryptographic key storage',
                            'req4_transmission' => 'Encrypt transmission over public networks',
                            'req4_1_strong_crypto' => 'Use strong cryptography and TLS',
                        ],
                    ],
                    'vulnerability_management' => [
                        'name' => 'Vulnerability Management Program',
                        'requirements' => [
                            'req5_malware' => 'Protect all systems against malware',
                            'req5_1_antivirus' => 'Deploy anti-virus on commonly affected systems',
                            'req6_secure_systems' => 'Develop and maintain secure systems',
                            'req6_1_security_patches' => 'Install critical patches within one month',
                            'req6_2_custom_code' => 'Protect custom code from vulnerabilities',
                            'req6_4_change_control' => 'Follow change control procedures',
                        ],
                    ],
                    'access_control' => [
                        'name' => 'Strong Access Control Measures',
                        'requirements' => [
                            'req7_restrict_access' => 'Restrict access to CHD by business need-to-know',
                            'req7_1_access_rights' => 'Define access needs for each role',
                            'req8_identify_users' => 'Identify users and authenticate access',
                            'req8_1_unique_ids' => 'Assign unique ID to each person',
                            'req8_3_mfa' => 'Multi-factor authentication for all access',
                            'req8_4_strong_auth' => 'Strong authentication requirements',
                            'req9_physical_access' => 'Restrict physical access to CHD',
                        ],
                    ],
                    'monitoring_testing' => [
                        'name' => 'Regular Monitoring and Testing',
                        'requirements' => [
                            'req10_track_monitor' => 'Track and monitor all access to network resources',
                            'req10_1_audit_trails' => 'Implement audit trails for all access',
                            'req11_test_security' => 'Test security systems and processes',
                            'req11_1_wireless_testing' => 'Test for wireless access points quarterly',
                            'req11_3_penetration_testing' => 'Annual penetration testing',
                            'req11_4_intrusion_detection' => 'Use intrusion detection/prevention',
                            'req11_5_change_detection' => 'Deploy change-detection mechanisms',
                        ],
                    ],
                    'security_policies' => [
                        'name' => 'Information Security Policy',
                        'requirements' => [
                            'req12_security_policy' => 'Maintain information security policy',
                            'req12_1_annual_review' => 'Annual policy review and update',
                            'req12_3_usage_policies' => 'Usage policies for critical technologies',
                            'req12_4_responsibilities' => 'Security responsibilities for all personnel',
                            'req12_5_responsibilities_assigned' => 'Assign security responsibilities',
                            'req12_6_security_awareness' => 'Security awareness program',
                            'req12_8_incident_response' => 'Incident response plan',
                            'req12_10_annual_risk' => 'Annual risk assessment process',
                        ],
                    ],
                ],
            ],

            'iso27001' => [
                'name' => 'ISO/IEC 27001:2022',
                'description' => 'Information Security Management System',
                'version' => '2022',
                'categories' => [
                    'organizational_controls' => [
                        'name' => 'Organizational Controls (37 controls)',
                        'requirements' => [
                            '5.1_policies' => 'Policies for information security',
                            '5.2_roles' => 'Information security roles and responsibilities',
                            '5.3_segregation' => 'Segregation of duties',
                            '5.4_management' => 'Management responsibilities',
                            '5.7_threat_intelligence' => 'Threat intelligence',
                            '5.8_projects' => 'Information security in project management',
                            '5.9_inventory' => 'Inventory of information and assets',
                            '5.10_acceptable_use' => 'Acceptable use of assets',
                            '5.11_return_assets' => 'Return of assets',
                            '5.12_classification' => 'Classification of information',
                            '5.14_information_transfer' => 'Information transfer',
                            '5.15_access_control' => 'Access control',
                            '5.23_cloud_security' => 'Information security for cloud services',
                            '5.30_ict_readiness' => 'ICT readiness for business continuity',
                        ],
                    ],
                    'people_controls' => [
                        'name' => 'People Controls (8 controls)',
                        'requirements' => [
                            '6.1_screening' => 'Background verification checks',
                            '6.2_employment_terms' => 'Terms and conditions of employment',
                            '6.3_awareness' => 'Information security awareness and training',
                            '6.4_disciplinary' => 'Disciplinary process',
                            '6.5_termination' => 'Responsibilities after termination',
                            '6.6_nda' => 'Confidentiality or non-disclosure agreements',
                            '6.7_remote_working' => 'Remote working',
                            '6.8_reporting' => 'Information security event reporting',
                        ],
                    ],
                    'physical_controls' => [
                        'name' => 'Physical Controls (14 controls)',
                        'requirements' => [
                            '7.1_physical_perimeter' => 'Physical security perimeters',
                            '7.2_physical_entry' => 'Physical entry controls',
                            '7.3_secure_areas' => 'Securing offices, rooms and facilities',
                            '7.4_monitoring' => 'Physical security monitoring',
                            '7.5_protecting_threats' => 'Protecting against physical threats',
                            '7.6_secure_areas_work' => 'Working in secure areas',
                            '7.7_clear_desk' => 'Clear desk and clear screen policy',
                            '7.8_equipment_siting' => 'Equipment siting and protection',
                            '7.9_assets_offsite' => 'Security of assets off-premises',
                            '7.10_storage_media' => 'Storage media',
                            '7.11_utilities' => 'Supporting utilities',
                            '7.12_cabling' => 'Cabling security',
                            '7.13_maintenance' => 'Equipment maintenance',
                            '7.14_disposal' => 'Secure disposal or reuse of equipment',
                        ],
                    ],
                    'technological_controls' => [
                        'name' => 'Technological Controls (34 controls)',
                        'requirements' => [
                            '8.1_user_devices' => 'User endpoint devices',
                            '8.2_privileged_access' => 'Privileged access rights',
                            '8.3_information_access' => 'Information access restriction',
                            '8.4_source_code' => 'Access to source code',
                            '8.5_secure_authentication' => 'Secure authentication',
                            '8.6_capacity' => 'Capacity management',
                            '8.7_malware' => 'Protection against malware',
                            '8.8_vulnerabilities' => 'Management of technical vulnerabilities',
                            '8.9_configuration' => 'Configuration management',
                            '8.10_information_deletion' => 'Information deletion',
                            '8.11_data_masking' => 'Data masking',
                            '8.12_data_leakage' => 'Data leakage prevention',
                            '8.16_monitoring' => 'Monitoring activities',
                            '8.23_web_filtering' => 'Web filtering',
                            '8.24_cryptography' => 'Use of cryptography',
                            '8.25_secure_development' => 'Secure development life cycle',
                            '8.28_secure_coding' => 'Secure coding',
                        ],
                    ],
                ],
            ],

            'nist_csf' => [
                'name' => 'NIST Cybersecurity Framework 2.0',
                'description' => 'Framework for improving critical infrastructure cybersecurity',
                'version' => '2.0 (February 2024)',
                'categories' => [
                    'govern' => [
                        'name' => 'Govern (GV)',
                        'requirements' => [
                            'gv_oc' => 'Organizational Context - Understanding mission and stakeholder expectations',
                            'gv_rm' => 'Risk Management Strategy - Priorities, constraints, and risk appetite',
                            'gv_rr' => 'Roles and Responsibilities - Cybersecurity roles established',
                            'gv_po' => 'Policy - Organizational cybersecurity policy established',
                            'gv_ov' => 'Oversight - Cybersecurity risk management oversight',
                            'gv_sc' => 'Cybersecurity Supply Chain Risk Management',
                        ],
                    ],
                    'identify' => [
                        'name' => 'Identify (ID)',
                        'requirements' => [
                            'id_am' => 'Asset Management - Physical and software inventory',
                            'id_be' => 'Business Environment - Organization role in supply chain',
                            'id_gv' => 'Governance - Policies and procedures',
                            'id_ra' => 'Risk Assessment - Identify and document risks',
                            'id_rm' => 'Risk Management Strategy - Priorities and constraints',
                            'id_sc' => 'Supply Chain Risk Management',
                        ],
                    ],
                    'protect' => [
                        'name' => 'Protect (PR)',
                        'requirements' => [
                            'pr_ac' => 'Identity Management and Access Control',
                            'pr_at' => 'Awareness and Training',
                            'pr_ds' => 'Data Security - Data at rest and in transit protection',
                            'pr_ip' => 'Information Protection Processes',
                            'pr_ma' => 'Maintenance - Timely maintenance and repairs',
                            'pr_pt' => 'Protective Technology - Technical security solutions',
                        ],
                    ],
                    'detect' => [
                        'name' => 'Detect (DE)',
                        'requirements' => [
                            'de_ae' => 'Anomalies and Events - Anomalous activity detection',
                            'de_cm' => 'Security Continuous Monitoring',
                            'de_dp' => 'Detection Processes - Maintained and tested',
                        ],
                    ],
                    'respond' => [
                        'name' => 'Respond (RS)',
                        'requirements' => [
                            'rs_rp' => 'Response Planning - Response processes executed',
                            'rs_co' => 'Communications - Coordination with stakeholders',
                            'rs_an' => 'Analysis - Understand attack and impact',
                            'rs_mi' => 'Mitigation - Prevent expansion of event',
                            'rs_im' => 'Improvements - Lessons learned',
                        ],
                    ],
                    'recover' => [
                        'name' => 'Recover (RC)',
                        'requirements' => [
                            'rc_rp' => 'Recovery Planning - Recovery processes executed',
                            'rc_im' => 'Improvements - Recovery planning improvements',
                            'rc_co' => 'Communications - Restoration activities coordinated',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Calculate compliance score for a documentation
     */
    public function calculateComplianceScore(ClientITDocumentation $documentation, string $framework): array
    {
        $frameworks = $this->getComplianceFrameworks();

        if (! isset($frameworks[$framework])) {
            return ['score' => 0, 'gaps' => [], 'recommendations' => []];
        }

        $totalRequirements = 0;
        $metRequirements = 0;
        $gaps = [];
        $recommendations = [];

        foreach ($frameworks[$framework]['categories'] as $categoryKey => $category) {
            foreach ($category['requirements'] as $reqKey => $requirement) {
                $totalRequirements++;

                // Check if requirement is met based on documentation fields
                if ($this->isRequirementMet($documentation, $framework, $categoryKey, $reqKey)) {
                    $metRequirements++;
                } else {
                    $gaps[] = [
                        'category' => $category['name'],
                        'requirement' => $requirement,
                        'key' => $reqKey,
                    ];

                    $recommendations[] = $this->getRecommendation($framework, $categoryKey, $reqKey);
                }
            }
        }

        $score = $totalRequirements > 0 ? round(($metRequirements / $totalRequirements) * 100, 2) : 0;

        return [
            'score' => $score,
            'total_requirements' => $totalRequirements,
            'met_requirements' => $metRequirements,
            'gaps' => $gaps,
            'recommendations' => array_filter($recommendations),
        ];
    }

    /**
     * Check if a specific requirement is met
     */
    private function isRequirementMet(ClientITDocumentation $doc, string $framework, string $category, string $requirement): bool
    {
        // This is a simplified check - in production, this would be more sophisticated
        // checking actual documentation content, procedures, controls, etc.

        $complianceData = $doc->compliance_requirements ?? [];

        // Check if framework is selected
        if (! in_array($framework, $complianceData)) {
            return false;
        }

        // Check for specific requirement indicators in various fields
        switch ($framework) {
            case 'gdpr':
                return $this->checkGDPRRequirement($doc, $category, $requirement);
            case 'hipaa':
                return $this->checkHIPAARequirement($doc, $category, $requirement);
            case 'soc2':
                return $this->checkSOC2Requirement($doc, $category, $requirement);
            case 'pci_dss':
                return $this->checkPCIDSSRequirement($doc, $category, $requirement);
            case 'iso27001':
                return $this->checkISO27001Requirement($doc, $category, $requirement);
            case 'nist_csf':
                return $this->checkNISTRequirement($doc, $category, $requirement);
            default:
                return false;
        }
    }

    /**
     * Check GDPR requirement
     */
    private function checkGDPRRequirement(ClientITDocumentation $doc, string $category, string $requirement): bool
    {
        // Check based on category and requirement
        if ($category === 'data_governance') {
            if ($requirement === 'legal_basis' && ! empty($doc->description)) {
                return true;
            }
            if ($requirement === 'privacy_policy' && str_contains(strtolower($doc->description ?? ''), 'privacy')) {
                return true;
            }
        }

        if ($category === 'security_measures') {
            if ($requirement === 'encryption' && ($doc->encryption_required ?? false)) {
                return true;
            }
            if ($requirement === 'access_controls' && $doc->access_level !== 'public') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check HIPAA requirement
     */
    private function checkHIPAARequirement(ClientITDocumentation $doc, string $category, string $requirement): bool
    {
        if ($category === 'technical_safeguards') {
            if ($requirement === 'encryption_decryption' && ($doc->encryption_required ?? false)) {
                return true;
            }
            if ($requirement === 'audit_logs' && ! empty($doc->audit_requirements)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check SOC 2 requirement
     */
    private function checkSOC2Requirement(ClientITDocumentation $doc, string $category, string $requirement): bool
    {
        // SOC 2 checks would verify Trust Service Criteria compliance
        return false;
    }

    /**
     * Check PCI DSS requirement
     */
    private function checkPCIDSSRequirement(ClientITDocumentation $doc, string $category, string $requirement): bool
    {
        if ($category === 'network_security' && $doc->network_diagram) {
            return true;
        }

        return false;
    }

    /**
     * Check ISO 27001 requirement
     */
    private function checkISO27001Requirement(ClientITDocumentation $doc, string $category, string $requirement): bool
    {
        // Check for ISO 27001 Annex A controls
        return false;
    }

    /**
     * Check NIST CSF requirement
     */
    private function checkNISTRequirement(ClientITDocumentation $doc, string $category, string $requirement): bool
    {
        // Check for NIST CSF function requirements
        return false;
    }

    /**
     * Get recommendation for missing requirement
     */
    private function getRecommendation(string $framework, string $category, string $requirement): ?string
    {
        $recommendations = [
            'gdpr' => [
                'data_governance' => [
                    'legal_basis' => 'Document the legal basis for processing personal data (consent, contract, legal obligation, vital interests, public task, or legitimate interests)',
                    'privacy_policy' => 'Create a comprehensive privacy policy that explains data collection, processing, storage, and sharing practices',
                ],
                'security_measures' => [
                    'encryption' => 'Implement encryption for personal data both at rest and in transit using industry-standard algorithms',
                    'access_controls' => 'Establish role-based access controls with the principle of least privilege',
                ],
            ],
            'hipaa' => [
                'technical_safeguards' => [
                    'encryption_decryption' => 'Implement NIST-approved encryption methods for all PHI storage and transmission',
                    'audit_logs' => 'Enable comprehensive audit logging for all access to PHI with regular review procedures',
                ],
            ],
        ];

        return $recommendations[$framework][$category][$requirement] ?? null;
    }

    /**
     * Get compliance dashboard data
     */
    public function getComplianceDashboard(ClientITDocumentation $documentation): array
    {
        $frameworks = ['gdpr', 'hipaa', 'soc2', 'pci_dss', 'iso27001', 'nist_csf'];
        $scores = [];
        $overallGaps = [];

        foreach ($frameworks as $framework) {
            if (in_array($framework, $documentation->compliance_requirements ?? [])) {
                $result = $this->calculateComplianceScore($documentation, $framework);
                $scores[$framework] = $result;
                $overallGaps = array_merge($overallGaps, $result['gaps']);
            }
        }

        return [
            'scores' => $scores,
            'overall_compliance' => $this->calculateOverallCompliance($scores),
            'critical_gaps' => $this->identifyCriticalGaps($overallGaps),
            'next_steps' => $this->generateNextSteps($scores),
        ];
    }

    /**
     * Calculate overall compliance percentage
     */
    private function calculateOverallCompliance(array $scores): float
    {
        if (empty($scores)) {
            return 0;
        }

        $total = array_sum(array_column($scores, 'score'));

        return round($total / count($scores), 2);
    }

    /**
     * Identify critical compliance gaps
     */
    private function identifyCriticalGaps(array $gaps): array
    {
        // Prioritize gaps based on criticality
        $criticalKeywords = ['encryption', 'breach', 'access_control', 'audit', 'risk_assessment'];
        $critical = [];

        foreach ($gaps as $gap) {
            foreach ($criticalKeywords as $keyword) {
                if (str_contains(strtolower($gap['requirement']), $keyword)) {
                    $critical[] = $gap;
                    break;
                }
            }
        }

        return array_slice($critical, 0, 5); // Return top 5 critical gaps
    }

    /**
     * Generate next steps based on compliance scores
     */
    private function generateNextSteps(array $scores): array
    {
        $steps = [];

        foreach ($scores as $framework => $result) {
            if ($result['score'] < 50) {
                $steps[] = "Critical: Address {$framework} compliance gaps urgently (currently at {$result['score']}%)";
            } elseif ($result['score'] < 80) {
                $steps[] = "Improve {$framework} compliance from {$result['score']}% to meet standards";
            }
        }

        return $steps;
    }
}
