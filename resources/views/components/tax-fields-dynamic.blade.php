@props([
    'categoryId' => null,
    'categoryType' => null,
    'productId' => null,
])

<div id="tax-fields-container" class="tax-fields-dynamic">
    <!-- Customer Selection for Tax Calculation -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden border-info mb-6">
        <div class="px-6 py-6 border-b border-gray-200 bg-gray-50 bg-gray-100">
            <div class="flex items-center justify-between">
                <h6 class="mb-0">
                    <i class="fas fa-user-circle text-cyan-600 dark:text-cyan-400"></i>
                    Customer & Address
                </h6>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-600" id="tax-confidence-badge" style="display: none;">Estimated</span>
            </div>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap -mx-4">
                <div class="md:w-2/3 px-6">
                    <label for="tax_customer_select" class="block text-sm font-medium text-gray-700 mb-1">
                        Customer <span class="text-red-600">*</span>
                        <i class="fas fa-info-circle text-gray-600 ml-1" 
                           x-data x-tooltip 
                           title="Customer address determines tax jurisdiction and applicable rates"></i>
                    </label>
                    <select class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="tax_customer_select" name="tax_customer_id" required>
                        <option value="">Select a customer...</option>
                        @php
                            $clients = \App\Models\Client::where('company_id', auth()->user()->company_id)
                                ->where('status', 'active')
                                ->orderBy('name')
                                ->get();
                        @endphp
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" 
                                    data-state="{{ $client->state }}" 
                                    data-city="{{ $client->city }}"
                                    data-zip="{{ $client->zip_code }}">
                                {{ $client->name }} {{ $client->company_name ? '(' . $client->company_name . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="text-red-600 text-sm mt-1">Please select a customer for tax calculation</div>
                </div>
                <div class="md:w-1/3 px-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tax Jurisdiction</label>
                    <div class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-plaintext" id="tax-jurisdiction-display">
                        <span class="text-gray-600">Select customer first</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tax Profile Information -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden border-primary mb-6" id="tax-profile-card" style="display: none;">
        <div class="px-6 py-6 border-b border-gray-200 bg-gray-50 bg-blue-600 text-white">
            <div class="flex items-center justify-between">
                <h6 class="mb-0">
                    <i class="fas fa-cogs"></i>
                    <span id="tax-profile-name">Tax Profile</span>
                </h6>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-dark" id="tax-engine-badge">Standard</span>
            </div>
        </div>
        <div class="p-6">
            <p class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-text text-gray-600 dark:text-gray-400 mb-6" id="tax-profile-description">
                Tax calculation profile for this service category
            </p>
            
            <!-- Required Fields Section -->
            <div id="tax-fields-section" style="display: none;">
                <h6 class="text-blue-600 mb-6">
                    <i class="fas fa-list-check"></i>
                    Required Information for Tax Calculation
                </h6>
                <div class="flex flex-wrap -mx-4" id="dynamic-tax-fields">
                    <!-- Fields will be dynamically inserted here based on category -->
                </div>
            </div>
        </div>
    </div>

    <!-- Tax Calculation Results -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border-success mb-6" id="tax-calculation-display" style="display: none;">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header bg-success text-white">
            <div class="flex items-center justify-between">
                <h6 class="mb-0">
                    <i class="fas fa-calculator"></i>
                    Tax Calculation Results
                </h6>
                <div class="flex items-center">
                    <div class="spinner-border spinner-border-sm mr-2" id="tax-loading" style="display: none;"></div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-light text-dark" id="tax-engine-type">Calculating...</span>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
            <!-- Tax Summary -->
            <div class="flex flex-wrap -mx-4">
                <div class="flex-1 px-6-md-8">
                    <div class="tax-summary p-6 bg-light rounded">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-bold">Service Price:</span>
                            <span class="h6 mb-0">$<span id="tax-subtotal">0.00</span></span>
                        </div>
                        <div class="flex justify-between items-center mb-2 text-gray-600 dark:text-gray-400">
                            <span>Tax (<span id="tax-rate">0.00</span>%):</span>
                            <span>+ $<span id="tax-amount">0.00</span></span>
                        </div>
                        <hr class="my-2">
                        <div class="flex justify-between items-center">
                            <strong class="h6 mb-0">Total Price:</strong>
                            <strong class="h5 mb-0 text-green-600">$<span id="tax-total">0.00</span></strong>
                        </div>
                    </div>
                </div>
                <div class="flex-1 px-6-md-4">
                    <div class="tax-metadata">
                        <h6 class="text-gray-600 dark:text-gray-400 mb-2">Tax Information</h6>
                        <small class="text-gray-600 dark:text-gray-400">
                            <div class="mb-1"><strong>Jurisdiction:</strong><br><span id="tax-jurisdiction">-</span></div>
                            <div class="mb-1"><strong>Address:</strong><br><span id="tax-address">-</span></div>
                            <div><strong>Tax Types:</strong><br><span id="tax-types-applied">-</span></div>
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Tax Breakdown -->
            <div id="tax-breakdown-section" class="mt-6" style="display: none;">
                <div class="flex items-center justify-between mb-2">
                    <h6 class="text-gray-600 dark:text-gray-400 mb-0">
                        <i class="fas fa-chart-pie"></i>
                        Tax Breakdown
                    </h6>
                    <button type="button" class="btn px-4 py-1 text-sm px-6 py-2 font-medium rounded-md transition-colors-outline-secondary" id="toggle-breakdown">
                        <i class="fas fa-chevron-down"></i> Show Details
                    </button>
                </div>
                <div id="tax-breakdown-list" class="collapse">
                    <!-- Tax breakdown items will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden fields to store tax data -->
    <input type="hidden" id="tax_profile_id" name="tax_profile_id">
    <input type="hidden" id="calculated_tax_amount" name="calculated_tax_amount">
    <input type="hidden" id="calculated_tax_rate" name="calculated_tax_rate">
    <input type="hidden" id="tax_jurisdiction_id" name="tax_jurisdiction_id">
    <input type="hidden" id="tax_calculation_data" name="tax_calculation_data">
</div>

@push('scripts')
<script>
class DynamicTaxFields {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.categoryId = {{ $categoryId ?? 'null' }};
        this.categoryType = '{{ $categoryType ?? '' }}';
        this.productId = {{ $productId ?? 'null' }};
        this.currentProfile = null;
        this.taxData = {};
        this.isCalculating = false;
        
        this.init();
    }
    
    init() {
        // Initialize tooltips
        this.initializeTooltips();
        
        // Listen for category changes
        const categorySelect = document.getElementById('category_id');
        if (categorySelect) {
            categorySelect.addEventListener('change', (e) => this.onCategoryChange(e.target.value));
        }
        
        // Listen for type changes (product vs service)  
        const typeInputs = document.querySelectorAll('input[name="type"]');
        typeInputs.forEach(input => {
            input.addEventListener('change', (e) => this.onTypeChange(e.target.value));
        });
        
        // Listen for customer selection with immediate feedback
        const customerSelect = document.getElementById('tax_customer_select');
        if (customerSelect) {
            customerSelect.addEventListener('change', () => {
                this.updateJurisdictionDisplay();
                this.calculateTax();
            });
            // Add validation styling
            customerSelect.addEventListener('invalid', () => {
                customerSelect.classList.add('border-red-500');
            });
            customerSelect.addEventListener('input', () => {
                customerSelect.classList.remove('border-red-500');
            });
        }
        
        // Listen for price changes with optimistic updates
        const priceInput = document.getElementById('price') || document.getElementById('base_price');
        if (priceInput) {
            priceInput.addEventListener('input', debounce(() => this.calculateTax(), 300));
        }
        
        // Initialize breakdown toggle
        this.initializeBreakdownToggle();
        
        // Load initial profile if category is set
        if (this.categoryId || this.categoryType) {
            this.loadTaxProfile();
        }
    }
    
    initializeTooltips() {
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[x-data x-tooltip]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    initializeBreakdownToggle() {
        const toggleBtn = document.getElementById('toggle-breakdown');
        const breakdownList = document.getElementById('tax-breakdown-list');
        
        if (toggleBtn && breakdownList) {
            toggleBtn.addEventListener('click', () => {
                const isCollapsed = !breakdownList.classList.contains('show');
                const icon = toggleBtn.querySelector('i');
                
                if (isCollapsed) {
                    breakdownList.classList.add('show');
                    toggleBtn.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Details';
                } else {
                    breakdownList.classList.remove('show');
                    toggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i> Show Details';
                }
            });
        }
    }
    
    async onCategoryChange(categoryId) {
        this.categoryId = categoryId;
        await this.loadTaxProfile();
    }
    
    async onTypeChange(type) {
        // Map product type to category type for tax purposes
        const typeMapping = {
            'service': 'professional_services',
            'product': 'general',
        };
        this.categoryType = typeMapping[type] || type;
        await this.loadTaxProfile();
    }
    
    updateJurisdictionDisplay() {
        const customerSelect = document.getElementById('tax_customer_select');
        const jurisdictionDisplay = document.getElementById('tax-jurisdiction-display');
        const confidenceBadge = document.getElementById('tax-confidence-badge');
        
        if (!customerSelect.value) {
            jurisdictionDisplay.innerHTML = '<span class="text-gray-600 dark:text-gray-400">Select customer first</span>';
            confidenceBadge.style.display = 'none';
            return;
        }
        
        const selectedOption = customerSelect.options[customerSelect.selectedIndex];
        const state = selectedOption.dataset.state;
        const city = selectedOption.dataset.city;
        
        if (state) {
            jurisdictionDisplay.innerHTML = `<span class="text-green-600">${city}, ${state}</span>`;
            confidenceBadge.style.display = 'inline-block';
            confidenceBadge.textContent = 'Calculated';
            confidenceBadge.className = 'badge bg-success';
        } else {
            jurisdictionDisplay.innerHTML = '<span class="text-yellow-600 dark:text-yellow-400">Address incomplete</span>';
            confidenceBadge.style.display = 'inline-block';
            confidenceBadge.textContent = 'Estimated';
            confidenceBadge.className = 'badge bg-warning';
        }
    }
    
    async loadTaxProfile() {
        if (!this.categoryId && !this.categoryType && !this.productId) {
            this.hideTaxProfile();
            return;
        }
        
        try {
            const params = new URLSearchParams();
            if (this.categoryId) params.append('category_id', this.categoryId);
            if (this.categoryType) params.append('category_type', this.categoryType);
            if (this.productId) params.append('product_id', this.productId);
            
            const response = await fetch(`/api/tax-engine/profile?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.currentProfile = result.data;
                this.displayTaxProfile();
                this.renderDynamicFields();
                this.showTaxProfile();
            } else {
                this.hideTaxProfile();
            }
        } catch (error) {
            console.error('Error loading tax profile:', error);
            this.hideTaxProfile();
        }
    }
    
    displayTaxProfile() {
        if (!this.currentProfile) return;
        
        // Update profile name and description
        const nameSpan = document.getElementById('tax-profile-name');
        const descSpan = document.getElementById('tax-profile-description');
        const engineBadge = document.getElementById('tax-engine-badge');
        
        if (nameSpan) {
            nameSpan.textContent = this.currentProfile.name;
        }
        
        if (descSpan) {
            descSpan.textContent = this.currentProfile.description || 'Tax calculation profile for this service category';
        }
        
        if (engineBadge) {
            const engineLabels = {
                'voip': 'VoIP Engine',
                'digital_services': 'Digital Tax',
                'equipment': 'Equipment Tax',
                'professional': 'Service Tax',
                'general': 'Standard Tax'
            };
            engineBadge.textContent = engineLabels[this.currentProfile.type] || 'Standard Tax';
        }
        
        // Update tax types display for later use
        if (this.currentProfile.tax_types) {
            document.getElementById('tax-types-applied').textContent = this.currentProfile.tax_types.join(', ');
        }
    }
    
    renderDynamicFields() {
        const fieldsContainer = document.getElementById('dynamic-tax-fields');
        const fieldsSection = document.getElementById('tax-fields-section');
        
        if (!fieldsContainer || !this.currentProfile) return;
        
        fieldsContainer.innerHTML = '';
        
        if (this.currentProfile.required_fields && this.currentProfile.required_fields.length > 0) {
            this.currentProfile.required_fields.forEach(field => {
                const fieldHtml = this.createFieldHtml(field);
                if (fieldHtml) {
                    fieldsContainer.insertAdjacentHTML('beforeend', fieldHtml);
                }
            });
            
            // Show the fields section
            if (fieldsSection) {
                fieldsSection.style.display = 'block';
            }
            
            // Add event listeners to new fields
            this.attachFieldListeners();
            
            // Re-initialize tooltips for new fields
            this.initializeTooltips();
        } else {
            // Hide fields section if no required fields
            if (fieldsSection) {
                fieldsSection.style.display = 'none';
            }
        }
    }
    
    createFieldHtml(field) {
        const fieldName = field.name;
        const fieldLabel = field.label || fieldName;
        const fieldType = field.type || 'text';
        const fieldHelp = field.help || '';
        const required = field.required !== false;
        const fieldId = `tax_field_${fieldName}`;
        
        switch (fieldType) {
            case 'number':
                return `
                    <div class="flex-1 px-6-md-6 mb-6">
                        <label for="${fieldId}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            ${fieldLabel}
                            ${required ? '<span class="text-red-600">*</span>' : ''}
                            ${fieldHelp ? `<i class="fas fa-info-circle text-gray-600 dark:text-gray-400 ml-1" x-data x-tooltip title="${fieldHelp}"></i>` : ''}
                        </label>
                        <input type="number" 
                               class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm tax-field" 
                               id="${fieldId}" 
                               name="tax_data[${fieldName}]"
                               data-field="${fieldName}"
                               min="${field.min || 0}"
                               ${field.max ? `max="${field.max}"` : ''}
                               ${field.step ? `step="${field.step}"` : ''}
                               ${field.default ? `value="${field.default}"` : ''}
                               ${required ? 'required' : ''}
                               aria-describedby="${fieldId}_help">
                        <div class="text-red-600 text-sm mt-1">Please enter a valid ${fieldLabel.toLowerCase()}</div>
                        ${fieldHelp && !field.tooltip ? `<small id="${fieldId}_help" class="form-text text-gray-600 dark:text-gray-400">${fieldHelp}</small>` : ''}
                    </div>
                `;
                
            case 'address':
                return `
                    <div class="flex-1 px-6-md-12 mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            ${fieldLabel}
                            ${required ? '<span class="text-red-600 dark:text-red-400">*</span>' : ''}
                            ${fieldHelp ? `<i class="fas fa-info-circle text-gray-600 dark:text-gray-400 ml-1" x-data x-tooltip title="${fieldHelp}"></i>` : ''}
                        </label>
                        <div class="flex flex-wrap -mx-4">
                            <div class="flex-1 px-6-md-6">
                                <input type="text" 
                                       class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm tax-field mb-2" 
                                       placeholder="City"
                                       id="${fieldId}_city" 
                                       name="tax_data[${fieldName}][city]"
                                       data-field="${fieldName}_city"
                                       aria-label="${fieldLabel} City">
                            </div>
                            <div class="flex-1 px-6-md-3">
                                <input type="text" 
                                       class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm tax-field mb-2" 
                                       placeholder="State"
                                       id="${fieldId}_state" 
                                       name="tax_data[${fieldName}][state]"
                                       data-field="${fieldName}_state"
                                       maxlength="2"
                                       style="text-transform: uppercase"
                                       aria-label="${fieldLabel} State">
                            </div>
                            <div class="flex-1 px-6-md-3">
                                <input type="text" 
                                       class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm tax-field mb-2" 
                                       placeholder="ZIP"
                                       id="${fieldId}_zip" 
                                       name="tax_data[${fieldName}][zip]"
                                       data-field="${fieldName}_zip"
                                       maxlength="10"
                                       aria-label="${fieldLabel} ZIP Code">
                            </div>
                        </div>
                        ${fieldHelp && !field.tooltip ? `<small class="form-text text-gray-600 dark:text-gray-400">${fieldHelp}</small>` : ''}
                    </div>
                `;
                
            case 'dimensions':
                return `
                    <div class="flex-1 px-6-md-12 mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">${fieldLabel}</label>
                        <div class="flex flex-wrap -mx-4">
                            <div class="flex-1 px-6-md-4">
                                <input type="number" 
                                       class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm tax-field" 
                                       placeholder="Length"
                                       id="tax_field_${fieldName}_length" 
                                       name="tax_data[${fieldName}][length]"
                                       data-field="${fieldName}_length"
                                       min="0"
                                       step="0.01">
                            </div>
                            <div class="flex-1 px-6-md-4">
                                <input type="number" 
                                       class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm tax-field" 
                                       placeholder="Width"
                                       id="tax_field_${fieldName}_width" 
                                       name="tax_data[${fieldName}][width]"
                                       data-field="${fieldName}_width"
                                       min="0"
                                       step="0.01">
                            </div>
                            <div class="flex-1 px-6-md-4">
                                <input type="number" 
                                       class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm tax-field" 
                                       placeholder="Height"
                                       id="tax_field_${fieldName}_height" 
                                       name="tax_data[${fieldName}][height]"
                                       data-field="${fieldName}_height"
                                       min="0"
                                       step="0.01">
                            </div>
                        </div>
                        <small class="form-text text-gray-600 dark:text-gray-400">${fieldHelp}</small>
                    </div>
                `;
                
            default:
                return `
                    <div class="flex-1 px-6-md-6 mb-6">
                        <label for="tax_field_${fieldName}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">${fieldLabel}</label>
                        <input type="text" 
                               class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm tax-field" 
                               id="tax_field_${fieldName}" 
                               name="tax_data[${fieldName}]"
                               data-field="${fieldName}">
                        <small class="form-text text-gray-600 dark:text-gray-400">${fieldHelp}</small>
                    </div>
                `;
        }
    }
    
    attachFieldListeners() {
        const taxFields = document.querySelectorAll('.tax-field');
        taxFields.forEach(field => {
            field.addEventListener('change', () => {
                this.collectTaxData();
                this.calculateTax();
            });
        });
    }
    
    collectTaxData() {
        this.taxData = {};
        const taxFields = document.querySelectorAll('.tax-field');
        
        taxFields.forEach(field => {
            const fieldName = field.dataset.field;
            const value = field.value;
            
            if (value) {
                // Handle nested fields (like address and dimensions)
                if (fieldName.includes('_')) {
                    const parts = fieldName.split('_');
                    const mainField = parts[0];
                    const subField = parts.slice(1).join('_');
                    
                    if (!this.taxData[mainField]) {
                        this.taxData[mainField] = {};
                    }
                    this.taxData[mainField][subField] = value;
                } else {
                    this.taxData[fieldName] = value;
                }
            }
        });
        
        // Store in hidden field
        document.getElementById('tax_calculation_data').value = JSON.stringify(this.taxData);
    }
    
    async calculateTax() {
        const priceInput = document.getElementById('price') || document.getElementById('base_price');
        const customerSelect = document.getElementById('tax_customer_select');
        
        const price = parseFloat(priceInput?.value) || 0;
        const customerId = customerSelect?.value;
        
        if (price <= 0 || !customerId) {
            this.hideTaxCalculation();
            return;
        }
        
        // Prevent multiple concurrent calculations
        if (this.isCalculating) {
            return;
        }
        
        this.isCalculating = true;
        this.showCalculationLoading();
        
        // Collect current tax data
        this.collectTaxData();
        
        // Get customer address
        const selectedOption = customerSelect.options[customerSelect.selectedIndex];
        const customerAddress = {
            state: selectedOption.dataset.state,
            city: selectedOption.dataset.city,
            zip: selectedOption.dataset.zip
        };
        
        try {
            const response = await fetch('/api/tax-engine/calculate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    base_price: price,
                    quantity: 1,
                    category_id: this.categoryId,
                    category_type: this.categoryType,
                    product_id: this.productId,
                    customer_id: customerId,
                    customer_address: customerAddress,
                    tax_data: this.taxData
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.displayTaxCalculation(result.data);
            } else {
                console.error('Tax calculation error:', result.error);
                this.showCalculationError(result.error);
            }
        } catch (error) {
            console.error('Error calculating tax:', error);
            this.showCalculationError('Failed to calculate tax. Please try again.');
        } finally {
            this.isCalculating = false;
            this.hideCalculationLoading();
        }
    }
    
    showCalculationLoading() {
        const loadingSpinner = document.getElementById('tax-loading');
        const engineType = document.getElementById('tax-engine-type');
        const display = document.getElementById('tax-calculation-display');
        
        if (loadingSpinner) {
            loadingSpinner.style.display = 'inline-block';
        }
        
        if (engineType) {
            engineType.textContent = 'Calculating...';
        }
        
        if (display) {
            display.style.display = 'block';
        }
    }
    
    hideCalculationLoading() {
        const loadingSpinner = document.getElementById('tax-loading');
        if (loadingSpinner) {
            loadingSpinner.style.display = 'none';
        }
    }
    
    showCalculationError(error) {
        const display = document.getElementById('tax-calculation-display');
        const engineType = document.getElementById('tax-engine-type');
        
        if (display) {
            display.className = 'card border-danger mb-4';
            display.querySelector('.card-header').className = 'card-header bg-danger text-white';
            display.style.display = 'block';
        }
        
        if (engineType) {
            engineType.textContent = 'Error';
            engineType.className = 'badge bg-light text-red-600 dark:text-red-400';
        }
        
        // Show error in the summary
        const cardBody = display.querySelector('.card-body');
        if (cardBody) {
            cardBody.innerHTML = `
                <div class="px-6 py-6 rounded bg-red-100 border border-red-400 text-red-700">
                    <i class="fas fa-exclamation-triangle"></i>
                    Tax calculation failed: ${error}
                </div>
            `;
        }
    }
    
    displayTaxCalculation(data) {
        const display = document.getElementById('tax-calculation-display');
        if (!display) return;
        
        // Reset to success state
        display.className = 'card border-success mb-4';
        display.querySelector('.card-header').className = 'card-header bg-success text-white';
        
        // Update summary
        document.getElementById('tax-subtotal').textContent = data.subtotal.toFixed(2);
        document.getElementById('tax-amount').textContent = data.tax_amount.toFixed(2);
        document.getElementById('tax-total').textContent = data.total.toFixed(2);
        document.getElementById('tax-rate').textContent = data.tax_rate.toFixed(2);
        
        // Update engine type
        const engineType = document.getElementById('tax-engine-type');
        if (engineType) {
            const engineLabels = {
                'voip': 'VoIP Tax Engine',
                'digital': 'Digital Services Tax',
                'equipment': 'Equipment Tax',
                'general': 'Standard Tax'
            };
            engineType.textContent = engineLabels[data.engine_used] || 'Tax Engine';
            engineType.className = 'badge bg-light text-dark';
        }
        
        // Update jurisdiction info
        if (data.jurisdictions && data.jurisdictions.length > 0) {
            const jurisdictionNames = data.jurisdictions.map(j => j.name).join(', ');
            document.getElementById('tax-jurisdiction').textContent = jurisdictionNames;
        } else {
            document.getElementById('tax-jurisdiction').textContent = 'Standard jurisdiction';
        }
        
        // Update address
        if (data.address_used) {
            const addr = data.address_used;
            const addressText = `${addr.city || ''}, ${addr.state || ''} ${addr.zip || ''}`.trim();
            document.getElementById('tax-address').textContent = addressText || 'Address not specified';
        }
        
        // Update tax types
        const taxTypesSpan = document.getElementById('tax-types-applied');
        if (taxTypesSpan) {
            if (data.tax_breakdown && Object.keys(data.tax_breakdown).length > 0) {
                const taxTypes = Object.values(data.tax_breakdown).map(tax => tax.name).join(', ');
                taxTypesSpan.textContent = taxTypes;
            } else {
                taxTypesSpan.textContent = 'Standard sales tax';
            }
        }
        
        // Update tax breakdown
        if (data.tax_breakdown && Object.keys(data.tax_breakdown).length > 0) {
            this.displayTaxBreakdown(data.tax_breakdown);
        }
        
        // Store calculated values in hidden fields
        document.getElementById('calculated_tax_amount').value = data.tax_amount;
        document.getElementById('calculated_tax_rate').value = data.tax_rate;
        if (data.tax_profile) {
            document.getElementById('tax_profile_id').value = data.tax_profile.id;
        }
        
        display.style.display = 'block';
    }
    
    displayTaxBreakdown(breakdown) {
        const section = document.getElementById('tax-breakdown-section');
        const list = document.getElementById('tax-breakdown-list');
        
        if (!section || !list) return;
        
        list.innerHTML = '';
        
        for (const [code, tax] of Object.entries(breakdown)) {
            const item = document.createElement('div');
            item.className = 'flex justify-between mb-1';
            item.innerHTML = `
                <span>${tax.name} (${tax.rate}%):</span>
                <span>$${tax.amount.toFixed(2)}</span>
            `;
            list.appendChild(item);
        }
        
        section.style.display = 'block';
    }
    
    showTaxProfile() {
        const profileCard = document.getElementById('tax-profile-card');
        if (profileCard) {
            profileCard.style.display = 'block';
        }
    }
    
    hideTaxProfile() {
        const profileCard = document.getElementById('tax-profile-card');
        if (profileCard) {
            profileCard.style.display = 'none';
        }
    }
    
    hideTaxCalculation() {
        const display = document.getElementById('tax-calculation-display');
        if (display) {
            display.style.display = 'none';
        }
    }
}

// Debounce helper
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    new DynamicTaxFields('tax-fields-container');
});
</script>
@endpush

<style>
.tax-fields-dynamic .card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: box-shadow 0.15s ease-in-out;
}

.tax-fields-dynamic .card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.tax-fields-dynamic .tax-summary {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.tax-fields-dynamic .tax-metadata {
    background-color: #f8f9fa;
    border-radius: 0.25rem;
    padding: 1rem;
}

#tax-breakdown-section {
    border-top: 1px solid #dee2e6;
    padding-top: 1rem;
}

.tax-field {
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.tax-field:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.tax-field.is-valid {
    border-color: #198754;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.93.93 4.83-4.83-.93-.93-3.9 3.9L.7 4.24z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.tax-field.border-red-500 {
    border-color: #dc3545;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 1.4 1.4 1.4-1.4M7.4 8.2 6 6.8l-1.4 1.4'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.card-header .badge {
    font-size: 0.75rem;
}

.block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 .text-red-600 dark:text-red-400 {
    font-weight: bold;
}

@media (max-width: 768px) {
    .tax-fields-dynamic .tax-summary .h5,
    .tax-fields-dynamic .tax-summary .h6 {
        font-size: 1rem;
    }
    
    .tax-fields-dynamic .tax-metadata {
        margin-top: 1rem;
    }
}

/* Loading state styles */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .tax-field, .card {
        transition: none;
    }
}
</style>
