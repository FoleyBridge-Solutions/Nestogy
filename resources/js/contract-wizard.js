/**
 * Contract Wizard Alpine.js Component
 * Manages the multi-step contract creation wizard
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('contractWizard', () => ({
        // Core State
        currentStep: 1,
        totalSteps: 5,
        selectedTemplate: null,
        
        // Form Data
        form: {
            title: '',
            contract_type: '',
            client_id: '',
            description: '',
            start_date: '',
            end_date: '',
            term_months: '',
            currency_code: 'USD',
            payment_terms: ''
        },
        
        // Template & Configuration
        variableValues: {},
        billingConfig: {
            model: '',
            base_rate: '',
            auto_assign_assets: false,
            auto_assign_new_assets: false,
            auto_assign_contacts: false,
            auto_assign_new_contacts: false
        },
        
        // Schedule Configurations
        infrastructureSchedule: {
            supportedAssetTypes: [],
            sla: {
                serviceTier: '',
                responseTimeHours: '',
                resolutionTimeHours: '',
                uptimePercentage: ''
            },
            coverageRules: {
                businessHours: '8x5',
                emergencySupport: 'included',
                autoAssignNewAssets: true,
                includeRemoteSupport: true,
                includeOnsiteSupport: false
            },
            exclusions: {
                assetTypes: '',
                services: ''
            }
        },
        
        pricingSchedule: {
            billingModel: '',
            basePricing: {
                monthlyBase: '',
                setupFee: '',
                hourlyRate: ''
            },
            perUnitPricing: {
                perUser: ''
            },
            assetTypePricing: {
                hypervisor_node: { enabled: false, price: '' },
                workstation: { enabled: false, price: '' },
                server: { enabled: false, price: '' },
                network_device: { enabled: false, price: '' },
                mobile_device: { enabled: false, price: '' },
                printer: { enabled: false, price: '' },
                storage: { enabled: false, price: '' },
                security_device: { enabled: false, price: '' }
            },
            tiers: [
                {
                    minQuantity: '',
                    maxQuantity: '',
                    price: '',
                    discountPercentage: ''
                }
            ],
            additionalFees: [],
            paymentTerms: {
                billingFrequency: 'monthly',
                terms: 'net_30',
                lateFeePercentage: ''
            }
        },
        
        additionalTerms: {
            termination: {
                noticePeriod: '30_days',
                earlyTerminationFee: '',
                forCause: ''
            },
            liability: {
                capType: 'contract_value',
                capAmount: '',
                excludedDamages: []
            },
            dataProtection: {
                classification: 'confidential',
                retentionPeriod: 'contract_term',
                complianceStandards: []
            },
            disputeResolution: {
                method: 'negotiation',
                governingLaw: 'client_state'
            },
            customClauses: [],
            amendments: {
                process: 'mutual_written',
                noticePeriod: '',
                allowPriceChanges: false,
                requireMutualConsent: true
            }
        },
        
        // Template-Specific Schedules
        telecomSchedule: {
            channelCount: 10,
            callingPlan: 'local_long_distance',
            internationalCalling: 'additional',
            emergencyServices: 'enabled',
            qos: {
                meanOpinionScore: '4.2',
                jitterMs: 30,
                packetLossPercent: 0.1,
                uptimePercent: '99.9',
                maxOutageDuration: '4 hours',
                latencyMs: 80,
                responseTimeHours: 1,
                resolutionTimeHours: 8,
                supportCoverage: '24x7'
            },
            carrier: {
                primary: '',
                backup: ''
            },
            protocol: 'sip',
            codecs: ['G.711', 'G.722'],
            compliance: {
                fccCompliant: true,
                karisLaw: true,
                rayBaums: true
            },
            security: {
                encryption: true,
                fraudProtection: true,
                callRecording: false
            }
        },
        
        hardwareSchedule: {
            selectedCategories: [],
            procurementModel: 'direct_resale',
            leadTimeDays: 5,
            leadTimeType: 'business_days',
            services: {
                basicInstallation: false,
                rackAndStack: false,
                cabling: false,
                powerConfiguration: false,
                basicConfiguration: false,
                advancedConfiguration: false,
                customConfiguration: false,
                testing: false,
                projectManagement: false,
                training: false,
                documentation: false,
                migration: false
            },
            sla: {
                installationTimeline: 'Within 5 business days',
                configurationTimeline: 'Within 2 business days',
                supportResponse: '4_hours'
            },
            warranty: {
                hardwarePeriod: '1_year',
                supportPeriod: '1_year',
                onSiteSupport: false,
                advancedReplacement: false,
                extendedOptions: []
            },
            pricing: {
                markupModel: 'fixed_percentage',
                categoryMarkup: {},
                volumeTiers: [],
                installationRate: '',
                configurationRate: '',
                projectManagementRate: '',
                travelRate: '',
                hardwarePaymentTerms: 'net_30',
                servicePaymentTerms: 'net_30',
                taxExempt: false
            }
        },
        
        complianceSchedule: {
            selectedFrameworks: [],
            scope: '',
            riskLevel: 'medium',
            industrySector: '',
            audits: {
                internal: false,
                external: false,
                penetrationTesting: false,
                vulnerabilityScanning: false,
                riskAssessment: false
            },
            frequency: {
                comprehensive: 'annually',
                interim: 'quarterly',
                vulnerability: 'monthly'
            },
            deliverables: {
                executiveSummary: false,
                detailedFindings: false,
                remediationPlan: false,
                complianceMatrix: false,
                dashboardReporting: false
            },
            training: {
                selectedPrograms: [],
                deliveryMethod: 'online',
                frequency: 'annually',
                tracking: {
                    attendance: false,
                    assessments: false,
                    certifications: false
                },
                minimumScore: 80
            },
            monitoring: {
                siem: false,
                logManagement: false,
                fileIntegrity: false,
                accessMonitoring: false,
                changeManagement: false
            },
            alerting: {
                critical: false,
                high: false,
                medium: false,
                low: false
            },
            notifications: {
                email: false,
                sms: false,
                dashboard: false
            },
            reporting: {
                executiveFrequency: 'quarterly',
                technicalFrequency: 'monthly',
                dashboardUpdates: 'daily'
            },
            response: {
                criticalTime: '1_hour',
                highTime: '4_hours',
                standardTime: '24_hours'
            },
            remediation: {
                immediateContainment: false,
                rootCauseAnalysis: false,
                correctiveActions: false,
                preventiveActions: false,
                verification: false,
                documentation: false
            },
            penalties: {
                tier1: 5.0,
                tier2: 10.0,
                tier3: 15.0,
                tier4: 25.0
            }
        },
        
        // UI State
        templateFilter: {
            category: '',
            billingModel: ''
        },
        activeScheduleTab: 'schedule_a',
        titleSuggestions: [],
        
        // Helper Data Arrays
        availableAssetTypes: [
            { value: 'hypervisor_node', label: 'Hypervisor Nodes', description: 'Proxmox, VMware, Hyper-V hosts', icon: 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2' },
            { value: 'workstation', label: 'Workstations', description: 'Desktop & laptop computers', icon: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
            { value: 'server', label: 'Servers', description: 'Physical & virtual servers', icon: 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2' },
            { value: 'network_device', label: 'Network Devices', description: 'Routers, switches, firewalls', icon: 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0' },
            { value: 'mobile_device', label: 'Mobile Devices', description: 'Phones, tablets, mobile endpoints', icon: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z' },
            { value: 'printer', label: 'Printers & Peripherals', description: 'Network printers, scanners, MFDs', icon: 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z' },
            { value: 'storage', label: 'Storage Systems', description: 'NAS, SAN, backup appliances', icon: 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2' },
            { value: 'security_device', label: 'Security Devices', description: 'Security cameras, access controls', icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' }
        ],
        
        billingModels: [
            { value: 'fixed', label: 'Fixed Rate', description: 'Single monthly fee' },
            { value: 'per_asset', label: 'Per Asset', description: 'Charge per device' },
            { value: 'per_user', label: 'Per User', description: 'Charge per user/contact' },
            { value: 'tiered', label: 'Tiered Pricing', description: 'Volume-based pricing' }
        ],
        
        serviceTiers: [
            { 
                value: 'bronze', 
                label: 'Bronze', 
                color: 'text-amber-600 dark:text-amber-400',
                responseTime: 8, 
                resolutionTime: 48, 
                uptime: 99.0, 
                coverage: '8x5'
            },
            { 
                value: 'silver', 
                label: 'Silver', 
                color: 'text-gray-600 dark:text-gray-400',
                responseTime: 4, 
                resolutionTime: 24, 
                uptime: 99.5, 
                coverage: '12x5'
            },
            { 
                value: 'gold', 
                label: 'Gold', 
                color: 'text-yellow-600 dark:text-yellow-400',
                responseTime: 2, 
                resolutionTime: 12, 
                uptime: 99.9, 
                coverage: '24x7'
            },
            { 
                value: 'platinum', 
                label: 'Platinum', 
                color: 'text-purple-600 dark:text-purple-400',
                responseTime: 1, 
                resolutionTime: 4, 
                uptime: 99.95, 
                coverage: '24x7'
            }
        ],
        
        // Hardware Categories for VAR Templates
        hardwareCategories: [
            { value: 'servers', label: 'Servers', description: 'Physical & virtual server hardware', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>' },
            { value: 'networking', label: 'Networking', description: 'Switches, routers, firewalls, wireless', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>' },
            { value: 'workstations', label: 'Workstations', description: 'Desktop & laptop computers', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>' },
            { value: 'storage', label: 'Storage', description: 'NAS, SAN, backup systems', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>' },
            { value: 'security', label: 'Security', description: 'Security appliances & cameras', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>' },
            { value: 'printers', label: 'Printers', description: 'Printers, scanners, MFDs', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>' }
        ],
        
        // Extended Warranty Options for Hardware
        extendedWarrantyOptions: [
            { value: 'extended_1yr', label: 'Extended 1 Year', description: '1 additional year beyond standard', markup: '+15%' },
            { value: 'extended_2yr', label: 'Extended 2 Years', description: '2 additional years beyond standard', markup: '+25%' },
            { value: 'premium_support', label: 'Premium Support', description: '24x7 support with 4hr response', markup: '+35%' },
            { value: 'next_business_day', label: 'Next Business Day', description: 'NBD replacement service', markup: '+20%' },
            { value: 'onsite_support', label: 'On-Site Support', description: 'Technician dispatch included', markup: '+40%' }
        ],
        
        // Compliance Frameworks
        complianceFrameworks: [
            { value: 'hipaa', label: 'HIPAA', description: 'Health Insurance Portability and Accountability Act', scope: 'Healthcare', auditFrequency: 'Annually', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>' },
            { value: 'sox', label: 'SOX', description: 'Sarbanes-Oxley Act', scope: 'Financial', auditFrequency: 'Annually', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>' },
            { value: 'pci_dss', label: 'PCI DSS', description: 'Payment Card Industry Data Security Standard', scope: 'Payment Processing', auditFrequency: 'Annually', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>' },
            { value: 'gdpr', label: 'GDPR', description: 'General Data Protection Regulation', scope: 'Data Privacy', auditFrequency: 'Bi-annually', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>' },
            { value: 'nist', label: 'NIST Cybersecurity Framework', description: 'National Institute of Standards and Technology', scope: 'Cybersecurity', auditFrequency: 'Annually', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>' },
            { value: 'iso_27001', label: 'ISO 27001', description: 'Information Security Management System', scope: 'Information Security', auditFrequency: 'Annually', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>' }
        ],
        
        // Training Programs for Compliance
        trainingPrograms: [
            { value: 'security_awareness', label: 'Security Awareness Training', description: 'General cybersecurity awareness', frequency: 'Annually' },
            { value: 'phishing_simulation', label: 'Phishing Simulation', description: 'Simulated phishing attacks and training', frequency: 'Quarterly' },
            { value: 'privacy_training', label: 'Privacy Training', description: 'Data privacy and protection training', frequency: 'Annually' },
            { value: 'incident_response', label: 'Incident Response Training', description: 'Security incident response procedures', frequency: 'Bi-annually' },
            { value: 'compliance_specific', label: 'Compliance-Specific Training', description: 'Framework-specific compliance training', frequency: 'Annually' },
            { value: 'role_based', label: 'Role-Based Training', description: 'Position-specific security training', frequency: 'Annually' }
        ],

        // Wizard Steps Configuration
        steps: [
            { id: 1, title: 'Template', subtitle: 'Select template', completed: false },
            { id: 2, title: 'Details', subtitle: 'Contract details', completed: false },
            { id: 3, title: 'Configuration', subtitle: 'Settings', completed: false },
            { id: 4, title: 'Review', subtitle: 'Verify details', completed: false },
            { id: 5, title: 'Create', subtitle: 'Complete', completed: false }
        ],

        // Initialization
        init() {
            this.loadSavedProgress();
            this.setupAutoSave();
            this.setupFormWatchers();
            // Generate initial variables
            this.generateVariableValues();
        },
        
        // Setup real-time form watchers
        setupFormWatchers() {
            // Watch for changes in key form fields and regenerate variables
            this.$watch('form', () => this.generateVariableValues(), { deep: true });
            this.$watch('infrastructureSchedule', () => this.generateVariableValues(), { deep: true });
            this.$watch('pricingSchedule', () => this.generateVariableValues(), { deep: true });
            this.$watch('additionalTerms', () => this.generateVariableValues(), { deep: true });
            this.$watch('billingConfig', () => this.generateVariableValues(), { deep: true });
            this.$watch('telecomSchedule', () => this.generateVariableValues(), { deep: true });
            this.$watch('hardwareSchedule', () => this.generateVariableValues(), { deep: true });
            this.$watch('complianceSchedule', () => this.generateVariableValues(), { deep: true });
        },

        // Computed Properties
        get completedSteps() {
            return this.steps.filter(step => step.completed).length;
        },
        
        get estimatedValue() {
            if (!this.selectedTemplate) return '$---.--';
            
            const baseValues = {
                'fixed': 2500,
                'per_asset': 1800,
                'per_contact': 2200,
                'tiered': 3500,
                'hybrid': 4200
            };
            
            const value = baseValues[this.selectedTemplate.billing_model] || 2500;
            return '$' + value.toLocaleString();
        },

        // Schedule Management Functions
        addPricingTier() {
            this.pricingSchedule.tiers.push({
                minQuantity: '',
                maxQuantity: '',
                price: '',
                discountPercentage: ''
            });
        },
        
        removePricingTier(index) {
            if (this.pricingSchedule.tiers.length > 1) {
                this.pricingSchedule.tiers.splice(index, 1);
            }
        },
        
        addAdditionalFee() {
            this.pricingSchedule.additionalFees.push({
                name: '',
                amount: '',
                frequency: 'one_time',
                type: 'fixed',
                description: ''
            });
        },
        
        removeAdditionalFee(index) {
            this.pricingSchedule.additionalFees.splice(index, 1);
        },
        
        addCustomClause() {
            this.additionalTerms.customClauses.push({
                title: '',
                content: ''
            });
        },
        
        removeCustomClause(index) {
            this.additionalTerms.customClauses.splice(index, 1);
        },
        
        updateSlaFromTier(tier) {
            this.infrastructureSchedule.sla.responseTimeHours = tier.responseTime;
            this.infrastructureSchedule.sla.resolutionTimeHours = tier.resolutionTime;
            this.infrastructureSchedule.sla.uptimePercentage = tier.uptime;
            this.infrastructureSchedule.coverageRules.businessHours = tier.coverage;
            
            // Auto-update variables when SLA changes
            this.generateVariableValues();
        },
        
        // Generate complete variable values from all form data
        generateVariableValues() {
            const variables = {};
            
            // Core Form Fields
            if (this.form.title) variables.contract_title = this.form.title;
            if (this.form.contract_type) variables.contract_type = this.form.contract_type;
            if (this.form.description) variables.contract_description = this.form.description;
            if (this.form.currency_code) variables.currency_code = this.form.currency_code;
            if (this.form.payment_terms) variables.payment_terms = this.form.payment_terms;
            
            // Infrastructure Schedule Variables
            if (this.infrastructureSchedule.sla.serviceTier) {
                variables.service_tier = this.infrastructureSchedule.sla.serviceTier;
            }
            if (this.infrastructureSchedule.sla.responseTimeHours) {
                variables.response_time_hours = this.infrastructureSchedule.sla.responseTimeHours.toString();
            }
            if (this.infrastructureSchedule.sla.resolutionTimeHours) {
                variables.resolution_time_hours = this.infrastructureSchedule.sla.resolutionTimeHours.toString();
            }
            if (this.infrastructureSchedule.sla.uptimePercentage) {
                variables.uptime_percentage = this.infrastructureSchedule.sla.uptimePercentage.toString();
            }
            
            // Coverage Rules
            if (this.infrastructureSchedule.coverageRules.businessHours) {
                variables.business_hours = this.formatBusinessHours(this.infrastructureSchedule.coverageRules.businessHours);
            }
            variables.emergency_support_included = this.infrastructureSchedule.coverageRules.emergencySupport === 'included';
            variables.includes_remote_support = this.infrastructureSchedule.coverageRules.includeRemoteSupport;
            variables.includes_onsite_support = this.infrastructureSchedule.coverageRules.includeOnsiteSupport;
            variables.auto_assign_new_assets = this.infrastructureSchedule.coverageRules.autoAssignNewAssets;
            
            // Asset Types
            if (this.infrastructureSchedule.supportedAssetTypes.length > 0) {
                variables.supported_asset_types = this.infrastructureSchedule.supportedAssetTypes.join(', ');
                
                // Individual asset type flags
                this.infrastructureSchedule.supportedAssetTypes.forEach(assetType => {
                    variables[`has_${assetType}_support`] = true;
                });
            }
            
            // Exclusions
            if (this.infrastructureSchedule.exclusions.assetTypes) {
                variables.excluded_asset_types = this.infrastructureSchedule.exclusions.assetTypes;
            }
            if (this.infrastructureSchedule.exclusions.services) {
                variables.excluded_services = this.infrastructureSchedule.exclusions.services;
            }
            
            // Pricing Schedule Variables
            if (this.pricingSchedule.billingModel) {
                variables.billing_model = this.pricingSchedule.billingModel;
            }
            
            // Base Pricing
            if (this.pricingSchedule.basePricing.monthlyBase) {
                variables.monthly_base_rate = this.formatCurrency(this.pricingSchedule.basePricing.monthlyBase);
            }
            if (this.pricingSchedule.basePricing.setupFee) {
                variables.setup_fee = this.formatCurrency(this.pricingSchedule.basePricing.setupFee);
            }
            if (this.pricingSchedule.basePricing.hourlyRate) {
                variables.hourly_rate = this.formatCurrency(this.pricingSchedule.basePricing.hourlyRate);
            }
            
            // Per-Unit Pricing
            if (this.pricingSchedule.perUnitPricing.perUser) {
                variables.per_user_rate = this.formatCurrency(this.pricingSchedule.perUnitPricing.perUser);
            }
            
            // Asset Type Pricing
            Object.keys(this.pricingSchedule.assetTypePricing).forEach(assetType => {
                const pricing = this.pricingSchedule.assetTypePricing[assetType];
                if (pricing.enabled && pricing.price) {
                    variables[`${assetType}_monthly_rate`] = this.formatCurrency(pricing.price);
                    variables[`${assetType}_billing_enabled`] = true;
                }
            });
            
            // Payment Terms
            if (this.pricingSchedule.paymentTerms.billingFrequency) {
                variables.billing_frequency = this.pricingSchedule.paymentTerms.billingFrequency;
            }
            if (this.pricingSchedule.paymentTerms.terms) {
                variables.payment_terms = this.pricingSchedule.paymentTerms.terms;
            }
            if (this.pricingSchedule.paymentTerms.lateFeePercentage) {
                variables.late_fee_percentage = this.pricingSchedule.paymentTerms.lateFeePercentage.toString();
            }
            
            // Tiered Pricing
            if (this.pricingSchedule.tiers.length > 0) {
                variables.has_tiered_pricing = true;
                variables.pricing_tiers = this.pricingSchedule.tiers.filter(tier => 
                    tier.minQuantity && tier.price
                );
            }
            
            // Additional Terms Variables
            if (this.additionalTerms.termination.noticePeriod) {
                variables.termination_notice_days = this.formatNoticePeriod(this.additionalTerms.termination.noticePeriod);
            }
            if (this.additionalTerms.termination.earlyTerminationFee) {
                variables.early_termination_fee = this.formatCurrency(this.additionalTerms.termination.earlyTerminationFee);
            }
            
            // Liability Terms
            if (this.additionalTerms.liability.capAmount) {
                variables.liability_cap_amount = this.formatCurrency(this.additionalTerms.liability.capAmount);
            }
            variables.liability_cap_type = this.additionalTerms.liability.capType;
            
            // Data Protection
            variables.data_classification = this.additionalTerms.dataProtection.classification;
            variables.data_retention_period = this.additionalTerms.dataProtection.retentionPeriod;
            if (this.additionalTerms.dataProtection.complianceStandards.length > 0) {
                variables.compliance_standards = this.additionalTerms.dataProtection.complianceStandards.join(', ');
            }
            
            // Dispute Resolution
            variables.dispute_resolution_method = this.additionalTerms.disputeResolution.method;
            variables.governing_law = this.additionalTerms.disputeResolution.governingLaw;
            
            // Telecom Schedule Variables (if applicable)
            if (this.getScheduleType() === 'telecom') {
                if (this.telecomSchedule.channelCount) {
                    variables.channel_count = this.telecomSchedule.channelCount.toString();
                }
                variables.calling_plan = this.telecomSchedule.callingPlan;
                variables.international_calling = this.telecomSchedule.internationalCalling;
                variables.emergency_services = this.telecomSchedule.emergencyServices;
                
                // QoS Variables
                if (this.telecomSchedule.qos.meanOpinionScore) {
                    variables.voice_quality_mos = this.telecomSchedule.qos.meanOpinionScore;
                }
                if (this.telecomSchedule.qos.jitterMs) {
                    variables.jitter_ms = this.telecomSchedule.qos.jitterMs.toString();
                }
                if (this.telecomSchedule.qos.packetLossPercent) {
                    variables.packet_loss_percent = this.telecomSchedule.qos.packetLossPercent.toString();
                }
            }
            
            // Hardware Schedule Variables (if applicable)
            if (this.getScheduleType() === 'hardware') {
                if (this.hardwareSchedule.selectedCategories.length > 0) {
                    variables.hardware_categories = this.hardwareSchedule.selectedCategories.join(', ');
                }
                variables.procurement_model = this.hardwareSchedule.procurementModel;
                variables.lead_time_days = this.hardwareSchedule.leadTimeDays.toString();
                
                // Installation Services
                Object.keys(this.hardwareSchedule.services).forEach(service => {
                    if (this.hardwareSchedule.services[service]) {
                        variables[`${service}_included`] = true;
                    }
                });
                
                // Hardware Pricing
                if (this.hardwareSchedule.pricing.installationRate) {
                    variables.installation_rate = this.formatCurrency(this.hardwareSchedule.pricing.installationRate);
                }
                if (this.hardwareSchedule.pricing.configurationRate) {
                    variables.configuration_rate = this.formatCurrency(this.hardwareSchedule.pricing.configurationRate);
                }
            }
            
            // Compliance Schedule Variables (if applicable)
            if (this.getScheduleType() === 'compliance') {
                if (this.complianceSchedule.selectedFrameworks.length > 0) {
                    variables.compliance_frameworks = this.complianceSchedule.selectedFrameworks.join(', ');
                }
                variables.compliance_scope = this.complianceSchedule.scope;
                variables.risk_level = this.complianceSchedule.riskLevel;
                variables.industry_sector = this.complianceSchedule.industrySector;
                
                // Audit Frequencies
                variables.comprehensive_audit_frequency = this.complianceSchedule.frequency.comprehensive;
                variables.interim_audit_frequency = this.complianceSchedule.frequency.interim;
                variables.vulnerability_scan_frequency = this.complianceSchedule.frequency.vulnerability;
            }
            
            // Billing Configuration
            if (this.billingConfig.model) {
                variables.billing_model = this.billingConfig.model;
            }
            if (this.billingConfig.base_rate) {
                variables.monthly_base_rate = this.formatCurrency(this.billingConfig.base_rate);
            }
            variables.auto_assign_assets = this.billingConfig.auto_assign_assets;
            variables.auto_assign_new_assets = this.billingConfig.auto_assign_new_assets;
            variables.auto_assign_contacts = this.billingConfig.auto_assign_contacts;
            variables.auto_assign_new_contacts = this.billingConfig.auto_assign_new_contacts;
            
            // Generate Service Tier Benefits
            variables.tier_benefits = this.generateTierBenefits();
            
            // Update the variableValues object
            this.variableValues = variables;
            
            return variables;
        },
        
        // Helper Methods for Variable Generation
        formatCurrency(amount) {
            if (!amount) return '';
            const num = parseFloat(amount.toString().replace(/[^0-9.-]/g, ''));
            return '$' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        
        formatBusinessHours(hours) {
            const hoursMap = {
                '8x5': '8 AM - 5 PM (Monday-Friday)',
                '12x5': '8 AM - 8 PM (Monday-Friday)', 
                '24x7': '24 hours (Monday-Sunday)',
                '24x5': '24 hours (Monday-Friday)'
            };
            return hoursMap[hours] || hours;
        },
        
        formatNoticePeriod(period) {
            const periodMap = {
                '30_days': '30 days',
                '60_days': '60 days',
                '90_days': '90 days',
                '6_months': '6 months',
                '1_year': '1 year'
            };
            return periodMap[period] || period.replace('_', ' ');
        },
        
        generateTierBenefits() {
            const tier = this.infrastructureSchedule.sla.serviceTier || 'silver';
            const selectedTier = this.serviceTiers.find(t => t.value === tier);
            
            if (!selectedTier) {
                return '- Standard monitoring and support\n- Business hours coverage\n- Email and phone support';
            }
            
            const benefits = [];
            
            // Coverage-based benefits
            if (selectedTier.coverage === '24x7') {
                benefits.push('- 24/7 monitoring and alerting');
                benefits.push('- Round-the-clock support coverage');
            } else if (selectedTier.coverage === '12x5') {
                benefits.push('- Extended hours monitoring (12x5)');
                benefits.push('- Business hours support coverage');
            } else {
                benefits.push('- Business hours monitoring (8x5)');
                benefits.push('- Standard support coverage');
            }
            
            // Response time benefits
            if (selectedTier.responseTime <= 2) {
                benefits.push('- Priority response within ' + selectedTier.responseTime + ' hours');
                benefits.push('- Dedicated support contact');
            } else if (selectedTier.responseTime <= 4) {
                benefits.push('- Rapid response within ' + selectedTier.responseTime + ' hours');
                benefits.push('- Priority phone support');
            } else {
                benefits.push('- Standard response within ' + selectedTier.responseTime + ' hours');
                benefits.push('- Email and phone support');
            }
            
            // Uptime benefits
            if (selectedTier.uptime >= 99.9) {
                benefits.push('- Premium uptime guarantee (' + selectedTier.uptime + '%)');
                benefits.push('- Proactive monitoring and maintenance');
            } else if (selectedTier.uptime >= 99.5) {
                benefits.push('- Standard uptime guarantee (' + selectedTier.uptime + '%)');
                benefits.push('- Regular monitoring and maintenance');
            }
            
            // Additional benefits based on options
            if (this.infrastructureSchedule.coverageRules.includeOnsiteSupport) {
                benefits.push('- On-site support included');
            } else if (this.infrastructureSchedule.coverageRules.includeRemoteSupport) {
                benefits.push('- Remote support and troubleshooting');
            }
            
            if (this.infrastructureSchedule.coverageRules.emergencySupport === 'included') {
                benefits.push('- Emergency after-hours support');
            }
            
            // Add reporting based on tier
            if (tier === 'platinum' || tier === 'gold') {
                benefits.push('- Weekly performance reports');
                benefits.push('- Monthly business reviews');
            } else if (tier === 'silver') {
                benefits.push('- Monthly performance reports');
                benefits.push('- Quarterly business reviews');
            } else {
                benefits.push('- Quarterly performance reports');
                benefits.push('- Annual business reviews');
            }
            
            return benefits.join('\n');
        },

        // Title Suggestion Generator
        generateSuggestions() {
            // Generate title suggestions based on selected template and client
            if (!this.form.client_id || !this.selectedTemplate) {
                return;
            }
            
            // This function can be expanded to provide intelligent title suggestions
            // For now, it's a placeholder to prevent console errors
            console.log('Generating title suggestions for:', this.selectedTemplate.name);
        },

        // Form Validation
        canProceedToNext() {
            switch (this.currentStep) {
                case 1:
                    return true; // Template selection is optional
                case 2:
                    const requiredFields = this.form.title && this.form.contract_type && this.form.client_id && this.form.start_date;
                    const dateValidation = this.form.end_date || this.form.term_months;
                    return requiredFields && dateValidation;
                case 3:
                    return this.validateScheduleConfiguration();
                case 4:
                    return this.validateAssetAssignment();
                default:
                    return true;
            }
        },
        
        isFormValid() {
            return this.canProceedToNext() && this.currentStep === this.totalSteps;
        },

        validateScheduleConfiguration() {
            // Basic infrastructure schedule validation
            if (this.infrastructureSchedule.supportedAssetTypes.length === 0) {
                this.showNotification('Please select at least one supported asset type', 'error');
                return false;
            }

            if (!this.infrastructureSchedule.sla.serviceTier) {
                this.showNotification('Please select a service tier', 'error');
                return false;
            }

            // Validate pricing schedule if present
            if (this.pricingSchedule.billingModel === 'tiered' && this.pricingSchedule.tiers.length === 0) {
                this.showNotification('Please configure at least one pricing tier', 'error');
                return false;
            }

            // Template-specific validation
            if (this.selectedTemplate) {
                const templateType = this.selectedTemplate.template_type || this.selectedTemplate.type;
                
                if (templateType === 'telecom' || templateType === 'voip') {
                    if (!this.telecomSchedule.channelCount || this.telecomSchedule.channelCount < 1) {
                        this.showNotification('Please specify the number of channels for telecom services', 'error');
                        return false;
                    }
                }
                
                if (templateType === 'hardware' || templateType === 'var') {
                    if (this.hardwareSchedule.selectedCategories.length === 0) {
                        this.showNotification('Please select at least one hardware category', 'error');
                        return false;
                    }
                }
                
                if (templateType === 'compliance') {
                    if (this.complianceSchedule.selectedFrameworks.length === 0) {
                        this.showNotification('Please select at least one compliance framework', 'error');
                        return false;
                    }
                }
            }

            return true;
        },
        
        validateAssetAssignment() {
            return true; // Asset assignment configuration is always valid
        },

        // Navigation
        nextStep() {
            if (this.canProceedToNext()) {
                this.steps[this.currentStep - 1].completed = true;
                this.currentStep++;
                this.saveProgress();
            }
        },
        
        previousStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
            }
        },

        // Progress Management
        hasProgress() {
            return this.form.title || this.form.client_id || this.selectedTemplate;
        },
        
        saveProgress() {
            const progressData = {
                currentStep: this.currentStep,
                steps: this.steps,
                selectedTemplate: this.selectedTemplate,
                form: this.form,
                variableValues: this.variableValues,
                billingConfig: this.billingConfig,
                infrastructureSchedule: this.infrastructureSchedule,
                pricingSchedule: this.pricingSchedule,
                additionalTerms: this.additionalTerms,
                telecomSchedule: this.telecomSchedule,
                hardwareSchedule: this.hardwareSchedule,
                complianceSchedule: this.complianceSchedule,
                templateFilter: this.templateFilter,
                timestamp: new Date().toISOString()
            };
            localStorage.setItem('contract_wizard_progress', JSON.stringify(progressData));
        },
        
        loadSavedProgress() {
            const saved = localStorage.getItem('contract_wizard_progress');
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    
                    // Only restore if data is recent (within 24 hours)
                    const dataAge = new Date() - new Date(data.timestamp);
                    if (dataAge < 24 * 60 * 60 * 1000) {
                        this.currentStep = data.currentStep || 1;
                        this.steps = data.steps || this.steps;
                        this.selectedTemplate = data.selectedTemplate;
                        this.form = { ...this.form, ...data.form };
                        this.variableValues = data.variableValues || {};
                        this.billingConfig = { ...this.billingConfig, ...data.billingConfig };
                        this.infrastructureSchedule = { ...this.infrastructureSchedule, ...data.infrastructureSchedule };
                        this.pricingSchedule = { ...this.pricingSchedule, ...data.pricingSchedule };
                        this.additionalTerms = { ...this.additionalTerms, ...data.additionalTerms };
                        this.telecomSchedule = { ...this.telecomSchedule, ...data.telecomSchedule };
                        this.hardwareSchedule = { ...this.hardwareSchedule, ...data.hardwareSchedule };
                        this.complianceSchedule = { ...this.complianceSchedule, ...data.complianceSchedule };
                        this.templateFilter = { ...this.templateFilter, ...data.templateFilter };
                    }
                } catch (e) {
                    console.warn('Failed to load saved progress:', e);
                }
            }
        },
        
        setupAutoSave() {
            // Auto-save every 30 seconds
            setInterval(() => {
                if (this.hasProgress()) {
                    this.saveProgress();
                }
            }, 30000);
        },

        // Form Submission
        handleSubmission(event) {
            console.log('🚀 Contract wizard form submission started');
            console.log('Form data:', {
                title: this.form.title,
                client_id: this.form.client_id,
                contract_type: this.form.contract_type,
                template: this.selectedTemplate?.name,
                currentStep: this.currentStep
            });
            
            // Log schedule data being submitted
            console.log('📋 Schedule data being submitted:', {
                infrastructureSchedule: {
                    supportedAssetTypes: this.infrastructureSchedule.supportedAssetTypes,
                    serviceTier: this.infrastructureSchedule.sla.serviceTier
                },
                pricingSchedule: {
                    billingModel: this.pricingSchedule.billingModel,
                    tiers: this.pricingSchedule.tiers.length
                },
                telecomSchedule: {
                    channelCount: this.telecomSchedule.channelCount,
                    callingPlan: this.telecomSchedule.callingPlan
                },
                hardwareSchedule: {
                    selectedCategories: this.hardwareSchedule.selectedCategories.length,
                    procurementModel: this.hardwareSchedule.procurementModel
                },
                complianceSchedule: {
                    selectedFrameworks: this.complianceSchedule.selectedFrameworks.length,
                    riskLevel: this.complianceSchedule.riskLevel
                }
            });
            
            if (!this.isFormValid()) {
                event.preventDefault();
                console.error('❌ Form validation failed');
                this.showNotification('Please complete all required fields', 'error');
                return;
            }
            
            // Additional schedule validation
            if (!this.validateScheduleConfiguration()) {
                event.preventDefault();
                console.error('❌ Schedule validation failed');
                return;
            }
            
            console.log('✅ Form validation passed, submitting contract...');
            
            // Clear saved progress on successful submission
            localStorage.removeItem('contract_wizard_progress');
            this.showNotification('Creating your contract...', 'success');
        },

        // Utility Functions
        showNotification(message, type = 'info') {
            // Create and show notification
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-4 rounded-xl shadow-2xl z-50 transform translate-x-full transition-all duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="font-medium">${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => notification.classList.remove('translate-x-full'), 100);
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => document.body.removeChild(notification), 300);
            }, 4000);
        },
        
        // Template-Specific Schedule Methods
        getScheduleType() {
            if (!this.selectedTemplate) return 'infrastructure';
            
            const templateType = this.selectedTemplate.type;
            
            // VoIP/Telecommunications templates
            if (['sip_trunking', 'unified_communications', 'international_calling', 'contact_center', 'e911', 'number_porting'].includes(templateType)) {
                return 'telecom';
            }
            
            // VAR/Hardware templates  
            if (['hardware_procurement', 'hardware_maintenance', 'hardware_installation', 'equipment_leasing', 'warranty_services'].includes(templateType)) {
                return 'hardware';
            }
            
            // Compliance templates
            if (['hipaa_compliance', 'sox_compliance', 'pci_compliance', 'gdpr_compliance', 'security_audit'].includes(templateType)) {
                return 'compliance';
            }
            
            // Default to infrastructure for MSP templates
            return 'infrastructure';
        },
        
        getScheduleALabel() {
            const scheduleType = this.getScheduleType();
            
            switch (scheduleType) {
                case 'telecom':
                    return 'Schedule A - Telecommunications & Service Levels';
                case 'hardware':
                    return 'Schedule A - Hardware Products & Services';
                case 'compliance':
                    return 'Schedule A - Compliance Framework & Requirements';
                default:
                    return 'Schedule A - Infrastructure & SLA';
            }
        },
        
        getScheduleDescription() {
            const scheduleType = this.getScheduleType();
            
            switch (scheduleType) {
                case 'telecom':
                    return 'Configure telecommunications services, QoS metrics, and compliance requirements';
                case 'hardware':
                    return 'Configure hardware procurement, installation services, and warranty terms';
                case 'compliance':
                    return 'Configure regulatory compliance requirements and audit schedules';
                default:
                    return 'Configure infrastructure coverage, pricing, and additional terms';
            }
        },
        
        getScheduleTypeLabel() {
            const scheduleType = this.getScheduleType();
            
            switch (scheduleType) {
                case 'telecom':
                    return 'Telecommunications Schedule Configuration';
                case 'hardware':
                    return 'Hardware & VAR Schedule Configuration';
                case 'compliance':
                    return 'Compliance Framework Configuration';
                default:
                    return 'Infrastructure & SLA Configuration';
            }
        },
        
        getScheduleTypeDetails() {
            const scheduleType = this.getScheduleType();
            
            switch (scheduleType) {
                case 'telecom':
                    return 'Channel capacity, calling plans, QoS metrics, and telecom compliance';
                case 'hardware':
                    return 'Product categories, installation services, warranty terms, and pricing models';
                case 'compliance':
                    return 'Regulatory frameworks, audit schedules, training programs, and monitoring';
                default:
                    return 'Asset coverage, service level agreements, and support configurations';
            }
        }
    }));
});