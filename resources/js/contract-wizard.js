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
            return true; // Schedule configuration is always valid for now
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
            if (!this.isFormValid()) {
                event.preventDefault();
                this.showNotification('Please complete all required fields', 'error');
                return;
            }
            
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