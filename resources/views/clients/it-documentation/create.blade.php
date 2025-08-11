@extends('layouts.app')

@section('title', 'Create IT Documentation')

@section('content')
<div class="space-y-6" x-data="documentationForm()">
    <!-- Page Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create IT Documentation</h1>
                    <p class="mt-1 text-sm text-gray-500">Comprehensive technical documentation and procedures</p>
                </div>
                <div class="flex items-center space-x-3">
                    <!-- Tab Configuration Button -->
                    <button type="button" @click="showTabConfig = true"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Configure Tabs
                    </button>
                    
                    <!-- Documentation Completeness -->
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">Completion:</span>
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="`width: ${completionPercentage}%`"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700" x-text="`${completionPercentage}%`"></span>
                    </div>
                    
                    <a href="{{ route('clients.it-documentation.index') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Selection Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4" x-show="!templateApplied">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-blue-800">Quick Start with Templates</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Select a template to automatically configure tabs and pre-fill common fields:</p>
                </div>
                <div class="mt-3">
                    <select @change="applyTemplate($event.target.value)" 
                            class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">-- Select a template --</option>
                        @foreach($templates as $key => $template)
                            <option value="{{ $key }}">{{ $template['name'] }} - {{ $template['description'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('clients.it-documentation.store') }}" method="POST" enctype="multipart/form-data" @submit="handleSubmit">
        @csrf
        
        <!-- Hidden field for enabled tabs -->
        <input type="hidden" name="enabled_tabs" :value="JSON.stringify(enabledTabs)">
        <input type="hidden" name="template_used" x-model="templateUsed">
        
        <!-- Tab Navigation - Dynamic -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex overflow-x-auto" aria-label="Tabs">
                    <!-- Dynamic Tab Buttons -->
                    <template x-for="tabId in enabledTabs" :key="tabId">
                        <button type="button" 
                                @click="activeTab = tabId" 
                                :class="activeTab === tabId ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors duration-200">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTabIcon(tabId)"/>
                                </svg>
                                <span x-text="getTabName(tabId)"></span>
                                <span x-show="!isTabValid(tabId)" class="w-2 h-2 bg-red-500 rounded-full"></span>
                            </div>
                        </button>
                    </template>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- General Tab -->
                <div x-show="activeTab === 'general' && enabledTabs.includes('general')" x-transition>
                    @include('clients.it-documentation.partials.tabs.general', ['clients' => $clients, 'categories' => $categories])
                </div>
                
                <!-- Only render other tabs if they are enabled -->
                <template x-if="enabledTabs.includes('technical')">
                    <div x-show="activeTab === 'technical'" x-transition>
                        @include('clients.it-documentation.partials.tabs.technical')
                    </div>
                </template>
                
                <template x-if="enabledTabs.includes('procedures')">
                    <div x-show="activeTab === 'procedures'" x-transition>
                        @include('clients.it-documentation.partials.tabs.procedures')
                    </div>
                </template>
                
                <template x-if="enabledTabs.includes('network')">
                    <div x-show="activeTab === 'network'" x-transition>
                        @include('clients.it-documentation.partials.tabs.network')
                    </div>
                </template>
                
                <template x-if="enabledTabs.includes('compliance')">
                    <div x-show="activeTab === 'compliance'" x-transition>
                        @include('clients.it-documentation.partials.tabs.compliance')
                    </div>
                </template>
                
                <template x-if="enabledTabs.includes('resources')">
                    <div x-show="activeTab === 'resources'" x-transition>
                        @include('clients.it-documentation.partials.tabs.resources')
                    </div>
                </template>
                
                <template x-if="enabledTabs.includes('testing')">
                    <div x-show="activeTab === 'testing'" x-transition>
                        <div class="text-gray-500">Testing & Validation tab content</div>
                    </div>
                </template>
                
                <template x-if="enabledTabs.includes('automation')">
                    <div x-show="activeTab === 'automation'" x-transition>
                        <div class="text-gray-500">Automation & Integration tab content</div>
                    </div>
                </template>
                
                <template x-if="enabledTabs.includes('monitoring')">
                    <div x-show="activeTab === 'monitoring'" x-transition>
                        <div class="text-gray-500">Monitoring & Metrics tab content</div>
                    </div>
                </template>
                
                <template x-if="enabledTabs.includes('history')">
                    <div x-show="activeTab === 'history'" x-transition>
                        <div class="text-gray-500">History & Versioning tab content</div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-between items-center">
            <div class="text-sm text-gray-500">
                <span x-text="enabledTabs.length"></span> tabs enabled
            </div>
            <div class="flex space-x-3">
                <button type="button" @click="saveDraft()"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Save as Draft
                </button>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Create Documentation
                </button>
            </div>
        </div>
    </form>

    <!-- Tab Configuration Modal -->
    @include('clients.it-documentation.partials.tab-configuration')
</div>
@endsection

@push('scripts')
<script>
function documentationForm() {
    return {
        activeTab: 'general',
        showTabConfig: false,
        templateApplied: false,
        templateUsed: '',
        
        // Only show General tab by default
        enabledTabs: ['general'],
        
        // Available tabs configuration
        availableTabs: @json($availableTabs),
        
        // Tab categories based on IT category
        tabRecommendations: {
            'runbook': ['general', 'technical', 'procedures', 'testing', 'monitoring'],
            'troubleshooting': ['general', 'procedures', 'technical', 'resources'],
            'architecture': ['general', 'technical', 'network', 'resources'],
            'backup_recovery': ['general', 'procedures', 'technical', 'testing', 'monitoring'],
            'monitoring': ['general', 'technical', 'monitoring', 'automation'],
            'change_management': ['general', 'procedures', 'history', 'testing'],
            'business_continuity': ['general', 'procedures', 'resources', 'testing', 'monitoring'],
            'user_guide': ['general', 'procedures', 'resources'],
            'compliance': ['general', 'compliance', 'procedures', 'history'],
            'vendor_procedure': ['general', 'procedures', 'resources', 'technical']
        },
        
        // Form data
        formData: {
            client_id: '{{ old('client_id', $selectedClientId) }}',
            it_category: '{{ old('it_category') }}',
            name: '',
            description: '',
            status: 'draft',
            effective_date: '',
            expiry_date: ''
        },
        
        // Dynamic arrays for various fields
        ipAddresses: [],
        ports: [],
        softwareVersions: [],
        apiEndpoints: [],
        procedureSteps: [],
        prerequisites: [],
        rollbackProcedures: [],
        externalResources: [],
        testCases: [],
        automationScripts: [],
        performanceMetrics: [],
        firewallRules: [],
        escalationPaths: [],
        complianceRequirements: [],
        networkSegments: [],
        networkDevices: [],
        supportContacts: [],
        licenses: [],
        uploadedFiles: [],
        
        // View modes
        procedureViewMode: 'text',
        
        // Diagram instances
        networkDiagram: null,
        procedureDiagram: null,
        
        init() {
            // Watch for category changes to suggest tabs
            this.$watch('formData.it_category', (value) => {
                if (value && !this.templateApplied) {
                    this.suggestTabsForCategory(value);
                }
            });
            
            // Initialize diagrams when tabs are shown
            this.$watch('activeTab', (value) => {
                this.$nextTick(() => {
                    if (value === 'network' && this.enabledTabs.includes('network')) {
                        this.initNetworkDiagram();
                    }
                    if (value === 'procedures' && this.enabledTabs.includes('procedures')) {
                        if (this.procedureViewMode === 'visual') {
                            this.initProcedureDiagram();
                        }
                    }
                });
            });
        },
        
        getTabName(tabId) {
            return this.availableTabs[tabId]?.name || tabId;
        },
        
        getTabIcon(tabId) {
            return this.availableTabs[tabId]?.icon || 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
        },
        
        isTabValid(tabId) {
            // Implement validation logic for each tab
            if (tabId === 'general') {
                return this.formData.client_id && this.formData.it_category && this.formData.name;
            }
            return true;
        },
        
        suggestTabsForCategory(category) {
            const recommended = this.tabRecommendations[category];
            if (recommended && !this.templateApplied) {
                // Show notification about recommended tabs
                this.$dispatch('notify', {
                    type: 'info',
                    message: `Recommended tabs for ${category}: ${recommended.join(', ')}. Click "Configure Tabs" to enable them.`
                });
            }
        },
        
        applyTemplate(templateKey) {
            if (!templateKey) return;
            
            // Get template data from templates array passed from controller
            const templates = @json($templates);
            const template = templates[templateKey];
            
            if (!template) {
                console.error('Template not found:', templateKey);
                return;
            }
            
            // Apply template configuration
            this.enabledTabs = template.enabled_tabs || ['general'];
            this.templateUsed = templateKey;
            this.templateApplied = true;
            
            // Set the active tab to the first enabled tab
            this.activeTab = this.enabledTabs[0];
            
            // Apply template data
            if (template.template_data) {
                // Apply form data
                if (template.category) {
                    this.formData.it_category = template.category;
                }
                
                // Apply array data
                if (template.template_data.procedure_steps) {
                    this.procedureSteps = template.template_data.procedure_steps;
                }
                if (template.template_data.test_cases) {
                    this.testCases = template.template_data.test_cases;
                }
                if (template.template_data.prerequisites) {
                    this.prerequisites = template.template_data.prerequisites;
                }
                if (template.template_data.ip_addresses) {
                    this.ipAddresses = template.template_data.ip_addresses;
                }
                if (template.template_data.firewall_rules) {
                    this.firewallRules = template.template_data.firewall_rules;
                }
                if (template.template_data.rollback_procedures) {
                    this.rollbackProcedures = template.template_data.rollback_procedures;
                }
                if (template.template_data.escalation_paths) {
                    this.escalationPaths = template.template_data.escalation_paths;
                }
                if (template.template_data.compliance_requirements) {
                    this.complianceRequirements = template.template_data.compliance_requirements;
                }
                if (template.template_data.rto) {
                    this.formData.rto = template.template_data.rto;
                }
                if (template.template_data.rpo) {
                    this.formData.rpo = template.template_data.rpo;
                }
                if (template.template_data.uptime_requirement) {
                    this.formData.uptime_requirement = template.template_data.uptime_requirement;
                }
            }
            
            // Show success message
            alert(`Template "${template.name}" applied successfully! ${this.enabledTabs.length} tabs enabled.`);
        },
        
        applyTabConfiguration() {
            this.showTabConfig = false;
            // Ensure general tab is always included
            if (!this.enabledTabs.includes('general')) {
                this.enabledTabs.unshift('general');
            }
            // Set active tab to first enabled tab
            this.activeTab = this.enabledTabs[0];
        },
        
        getCategoryName() {
            const categories = @json($categories);
            return categories[this.formData.it_category] || '';
        },
        
        getRecommendedTabs() {
            const recommended = this.tabRecommendations[this.formData.it_category];
            return recommended ? recommended.map(t => this.getTabName(t)).join(', ') : 'None';
        },
        
        get completionPercentage() {
            let completed = 0;
            let total = this.enabledTabs.length;
            
            // Check completion for each enabled tab
            this.enabledTabs.forEach(tabId => {
                if (this.isTabComplete(tabId)) {
                    completed++;
                }
            });
            
            return total > 0 ? Math.round((completed / total) * 100) : 0;
        },
        
        isTabComplete(tabId) {
            switch(tabId) {
                case 'general':
                    return !!(this.formData.client_id && this.formData.it_category && 
                             this.formData.name && this.formData.description);
                case 'technical':
                    return this.ipAddresses.length > 0 || this.ports.length > 0 || 
                           this.softwareVersions.length > 0;
                case 'procedures':
                    return this.procedureSteps.length > 0;
                case 'testing':
                    return this.testCases.length > 0;
                // Add more tab completion checks
                default:
                    return false;
            }
        },
        
        saveDraft() {
            this.formData.status = 'draft';
            document.querySelector('form').submit();
        },
        
        handleSubmit(e) {
            // Prepare arrays for submission
            const form = e.target;
            
            // Add hidden inputs for arrays
            this.ipAddresses.forEach((ip, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `ip_addresses[${index}]`;
                input.value = JSON.stringify(ip);
                form.appendChild(input);
            });
            
            // Similar for other arrays...
            
            return true;
        },
        
        // Array management methods
        addIpAddress() {
            this.ipAddresses.push({ address: '', description: '' });
        },
        
        removeIpAddress(index) {
            this.ipAddresses.splice(index, 1);
        },
        
        addPort() {
            this.ports.push({ number: '', protocol: 'TCP', service: '' });
        },
        
        removePort(index) {
            this.ports.splice(index, 1);
        },
        
        addSoftwareVersion() {
            this.softwareVersions.push({ name: '', version: '', vendor: '' });
        },
        
        removeSoftwareVersion(index) {
            this.softwareVersions.splice(index, 1);
        },
        
        addApiEndpoint() {
            this.apiEndpoints.push({ method: 'GET', url: '', description: '' });
        },
        
        removeApiEndpoint(index) {
            this.apiEndpoints.splice(index, 1);
        },
        
        addProcedureStep() {
            this.procedureSteps.push({ 
                order: this.procedureSteps.length + 1, 
                title: '', 
                description: '', 
                duration: '' 
            });
        },
        
        removeProcedureStep(index) {
            this.procedureSteps.splice(index, 1);
            // Re-order remaining steps
            this.procedureSteps.forEach((step, i) => {
                step.order = i + 1;
            });
        },
        
        addTestCase() {
            this.testCases.push({ name: '', description: '', expected_result: '' });
        },
        
        removeTestCase(index) {
            this.testCases.splice(index, 1);
        },
        
        toggleTab(tabId) {
            const index = this.enabledTabs.indexOf(tabId);
            if (index > -1) {
                // Remove tab if it exists (unless it's general which is required)
                if (tabId !== 'general') {
                    this.enabledTabs.splice(index, 1);
                }
            } else {
                // Add tab if it doesn't exist
                this.enabledTabs.push(tabId);
            }
        },
        
        // Prerequisite methods
        addPrerequisite() {
            this.prerequisites.push({ requirement: '' });
        },
        
        removePrerequisite(index) {
            this.prerequisites.splice(index, 1);
        },
        
        // Rollback procedure methods
        addRollbackProcedure() {
            this.rollbackProcedures.push({ title: '', description: '' });
        },
        
        removeRollbackProcedure(index) {
            this.rollbackProcedures.splice(index, 1);
        },
        
        // Network segment methods
        addNetworkSegment() {
            this.networkSegments.push({ 
                name: '', 
                subnet: '', 
                vlan: '', 
                description: '' 
            });
        },
        
        removeNetworkSegment(index) {
            this.networkSegments.splice(index, 1);
        },
        
        // Network device methods
        addNetworkDevice() {
            this.networkDevices.push({ 
                hostname: '', 
                type: '', 
                ip_address: '', 
                mac_address: '',
                manufacturer: '',
                model: '',
                location: '',
                notes: ''
            });
        },
        
        removeNetworkDevice(index) {
            this.networkDevices.splice(index, 1);
        },
        
        // Compliance methods
        addComplianceRequirement() {
            this.complianceRequirements.push({
                framework: 'Custom',
                requirement: '',
                implementation: '',
                status: 'compliant',
                review_date: '',
                evidence: '',
                auto_generated: false
            });
        },
        
        removeComplianceRequirement(index) {
            this.complianceRequirements.splice(index, 1);
        },
        
        updateComplianceRequirements() {
            // Get selected frameworks
            const checkboxes = document.querySelectorAll('input[name="compliance_frameworks[]"]:checked');
            const selectedFrameworks = Array.from(checkboxes).map(cb => cb.value);
            
            // Remove auto-generated requirements for unselected frameworks
            this.complianceRequirements = this.complianceRequirements.filter(req => 
                !req.auto_generated || selectedFrameworks.includes(req.framework.toLowerCase())
            );
            
            // Add requirements for newly selected frameworks
            selectedFrameworks.forEach(framework => {
                const existingForFramework = this.complianceRequirements.some(req => 
                    req.framework.toLowerCase() === framework && req.auto_generated
                );
                
                if (!existingForFramework) {
                    // Add framework-specific requirements
                    const requirements = this.getFrameworkRequirements(framework);
                    requirements.forEach(req => {
                        this.complianceRequirements.push({
                            framework: framework.toUpperCase(),
                            requirement: req,
                            implementation: '',
                            status: 'compliant',
                            review_date: '',
                            evidence: '',
                            auto_generated: true
                        });
                    });
                }
            });
        },
        
        getFrameworkRequirements(framework) {
            const requirements = {
                'gdpr': [
                    'Data minimization and purpose limitation',
                    'Consent management and withdrawal',
                    'Right to erasure (Right to be forgotten)',
                    'Data portability',
                    'Privacy by design and default'
                ],
                'hipaa': [
                    'Access controls and user authentication',
                    'Encryption of PHI at rest and in transit',
                    'Audit logs and monitoring',
                    'Business Associate Agreements (BAAs)',
                    'Incident response and breach notification'
                ],
                'soc2': [
                    'Security monitoring and incident response',
                    'Change management controls',
                    'Logical and physical access controls',
                    'Risk assessment and management',
                    'Vendor management'
                ],
                'pci_dss': [
                    'Network segmentation',
                    'Cardholder data encryption',
                    'Regular security testing',
                    'Access control measures',
                    'Security awareness training'
                ],
                'iso27001': [
                    'Information security policy',
                    'Risk assessment and treatment',
                    'Asset management',
                    'Human resource security',
                    'Physical and environmental security'
                ],
                'nist': [
                    'Identify critical assets and risks',
                    'Protect with appropriate safeguards',
                    'Detect cybersecurity events',
                    'Respond to detected events',
                    'Recover and restore capabilities'
                ]
            };
            
            return requirements[framework] || [];
        },
        
        getSelectedFrameworks() {
            const checkboxes = document.querySelectorAll('input[name="compliance_frameworks[]"]:checked');
            return Array.from(checkboxes).map(cb => cb.value.toUpperCase());
        },
        
        getComplianceScore(framework) {
            const frameworkReqs = this.complianceRequirements.filter(req => 
                req.framework.toLowerCase() === framework.toLowerCase()
            );
            
            if (frameworkReqs.length === 0) return 0;
            
            const compliant = frameworkReqs.filter(req => req.status === 'compliant').length;
            return Math.round((compliant / frameworkReqs.length) * 100);
        },
        
        // JointJS Diagram initialization
        initProcedureDiagram() {
            if (!this.procedureDiagram && document.getElementById('procedure-diagram')) {
                import('/js/it-documentation-diagram.js').then(module => {
                    this.procedureDiagram = new module.ITDocumentationDiagram('procedure-diagram');
                    // Convert procedure steps to diagram if they exist
                    if (this.procedureSteps.length > 0) {
                        this.convertStepsToDiagram();
                    }
                });
            }
        },
        
        initNetworkDiagram() {
            if (!this.networkDiagram && document.getElementById('network-diagram')) {
                import('/js/it-documentation-diagram.js').then(module => {
                    this.networkDiagram = new module.ITDocumentationDiagram('network-diagram');
                });
            }
        },
        
        convertStepsToDiagram() {
            // Convert text procedure steps to visual diagram
            if (this.procedureDiagram && this.procedureSteps.length > 0) {
                // Clear existing diagram
                this.procedureDiagram.clearDiagram();
                
                // Add shapes for each step
                let prevElement = null;
                this.procedureSteps.forEach((step, index) => {
                    let element;
                    const y = 100 + (index * 120);
                    
                    switch(step.type) {
                        case 'decision':
                            // Create diamond shape for decisions
                            element = this.procedureDiagram.addRouter(300, y);
                            break;
                        case 'automated':
                            // Create server shape for automated steps
                            element = this.procedureDiagram.addServer(300, y);
                            break;
                        default:
                            // Create rectangle for manual steps
                            element = this.procedureDiagram.addWorkstation(300, y);
                    }
                    
                    // Update label with step title
                    if (element && step.title) {
                        element.attr('label/text', step.title);
                    }
                    
                    // Connect to previous element
                    if (prevElement && element) {
                        const link = new joint.shapes.standard.Link();
                        link.source(prevElement);
                        link.target(element);
                        this.procedureDiagram.graph.addCell(link);
                    }
                    
                    prevElement = element;
                });
            }
        },
        
        importNetworkDiagram() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.json';
            input.onchange = (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        try {
                            const data = JSON.parse(event.target.result);
                            if (this.networkDiagram) {
                                this.networkDiagram.importDiagram(data);
                            }
                        } catch (error) {
                            alert('Failed to import diagram. Please check the file format.');
                        }
                    };
                    reader.readAsText(file);
                }
            };
            input.click();
        },
        
        // External resource methods
        addExternalResource() {
            this.externalResources.push({
                title: '',
                type: 'documentation',
                url: '',
                credentials: '',
                description: ''
            });
        },
        
        removeExternalResource(index) {
            this.externalResources.splice(index, 1);
        },
        
        // Support contact methods
        addSupportContact() {
            this.supportContacts.push({
                name: '',
                role: '',
                type: 'internal',
                email: '',
                phone: '',
                availability: '',
                notes: ''
            });
        },
        
        removeSupportContact(index) {
            this.supportContacts.splice(index, 1);
        },
        
        // License methods
        addLicense() {
            this.licenses.push({
                software: '',
                key: '',
                seats: '',
                purchase_date: '',
                expiry_date: '',
                cost: '',
                vendor: ''
            });
        },
        
        removeLicense(index) {
            this.licenses.splice(index, 1);
        },
        
        // File upload methods
        removeUploadedFile(index) {
            this.uploadedFiles.splice(index, 1);
        }
    }
}
</script>
@endpush