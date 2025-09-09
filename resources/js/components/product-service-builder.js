/**
 * Product/Service Builder Component
 * Advanced JavaScript for interactive product and service creation
 */

class ProductServiceBuilder {
    constructor() {
        this.init();
        this.bindEvents();
        this.setupValidation();
        this.initializeCalculators();
    }

    init() {
        this.elements = {
            // Type selection
            typeRadios: document.querySelectorAll('input[name="type"]'),
            productFields: document.getElementById('product-fields'),
            serviceFields: document.getElementById('service-fields'),
            
            // Service-specific
            unitType: document.getElementById('unit_type'),
            billingModel: document.getElementById('billing_model'),
            subscriptionFields: document.getElementById('subscription-billing-fields'),
            billingCycle: document.getElementById('billing_cycle'),
            billingInterval: document.getElementById('billing_interval'),
            
            // Pricing
            basePrice: document.getElementById('base_price') || document.getElementById('price'),
            cost: document.getElementById('cost'),
            pricingModel: document.getElementById('pricing_model'),
            
            // Calculators
            pricingCalculator: document.getElementById('pricing-calculator'),
            profitMarginDisplay: document.getElementById('profit-margin'),
            revenueProjection: document.getElementById('revenue-projection'),
            
            // Forms
            form: document.querySelector('form'),
            submitButton: document.querySelector('button[type="submit"]'),
            
            // Dynamic sections
            tieredPricingSection: document.getElementById('tiered-pricing-section'),
            volumeDiscountSection: document.getElementById('volume-discount-section'),
            usagePricingSection: document.getElementById('usage-pricing-section')
        };

        // Initialize component states
        this.state = {
            currentType: 'product',
            billingModel: 'one_time',
            pricingModel: 'fixed',
            tiers: [],
            validationErrors: {},
            unsavedChanges: false
        };

        // Create missing elements if needed
        this.createMissingElements();
    }

    createMissingElements() {
        // Create pricing calculator if it doesn't exist
        if (!this.elements.pricingCalculator) {
            this.createPricingCalculator();
        }

        // Create tiered pricing section if needed
        if (!this.elements.tieredPricingSection && this.elements.pricingModel) {
            this.createTieredPricingSection();
        }

        // Create validation feedback containers
        this.createValidationContainers();
    }

    createPricingCalculator() {
        const calculatorHtml = `
            <div id="pricing-calculator" class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-calculator text-primary"></i>
                        Live Pricing Calculator
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Quantity</label>
                            <input type="number" id="calc-quantity" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-md-4" id="billing-periods-section" style="display: none;">
                            <label class="form-label">Billing Periods</label>
                            <input type="number" id="calc-billing-periods" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-md-4" id="usage-amount-section" style="display: none;">
                            <label class="form-label">Usage Amount</label>
                            <input type="number" id="calc-usage" class="form-control" value="0" min="0" step="0.01">
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="pricing-result">
                                <label class="text-muted">Base Price</label>
                                <div id="calc-base-price" class="fs-5 fw-bold text-success">$0.00</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="pricing-result">
                                <label class="text-muted">Total Price</label>
                                <div id="calc-total-price" class="fs-4 fw-bold text-primary">$0.00</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-2" id="profit-analysis" style="display: none;">
                        <div class="col-md-4">
                            <small class="text-muted">Cost</small>
                            <div id="calc-total-cost" class="fw-bold">$0.00</div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Profit</small>
                            <div id="calc-profit" class="fw-bold text-success">$0.00</div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Margin</small>
                            <div id="calc-margin" class="fw-bold text-info">0%</div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insert calculator after pricing section
        const pricingCard = document.querySelector('.card:has(#base_price, #price)');
        if (pricingCard) {
            pricingCard.insertAdjacentHTML('afterend', calculatorHtml);
            this.elements.pricingCalculator = document.getElementById('pricing-calculator');
        }
    }

    createTieredPricingSection() {
        const tieredHtml = `
            <div id="tiered-pricing-section" class="mt-3" style="display: none;">
                <h6 class="mb-3">Pricing Tiers</h6>
                <div id="pricing-tiers-container">
                    <!-- Pricing tiers will be added here -->
                </div>
                <button type="button" id="add-pricing-tier" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-plus"></i> Add Pricing Tier
                </button>
            </div>
        `;

        if (this.elements.pricingModel) {
            this.elements.pricingModel.closest('.card-body').insertAdjacentHTML('beforeend', tieredHtml);
            this.elements.tieredPricingSection = document.getElementById('tiered-pricing-section');
        }
    }

    createValidationContainers() {
        // Add real-time validation feedback containers
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (!input.nextElementSibling?.classList.contains('validation-feedback')) {
                const feedback = document.createElement('div');
                feedback.className = 'validation-feedback';
                feedback.style.display = 'none';
                input.parentNode.insertBefore(feedback, input.nextSibling);
            }
        });
    }

    bindEvents() {
        // Type selection events
        this.elements.typeRadios.forEach(radio => {
            radio.addEventListener('change', (e) => this.handleTypeChange(e));
        });

        // Billing model changes
        if (this.elements.billingModel) {
            this.elements.billingModel.addEventListener('change', (e) => this.handleBillingModelChange(e));
        }

        // Pricing model changes
        if (this.elements.pricingModel) {
            this.elements.pricingModel.addEventListener('change', (e) => this.handlePricingModelChange(e));
        }

        // Price calculation events
        ['input', 'change'].forEach(eventType => {
            if (this.elements.basePrice) {
                this.elements.basePrice.addEventListener(eventType, () => this.updateCalculations());
            }
            if (this.elements.cost) {
                this.elements.cost.addEventListener(eventType, () => this.updateCalculations());
            }
        });

        // Form submission
        if (this.elements.form) {
            this.elements.form.addEventListener('submit', (e) => this.handleFormSubmission(e));
        }

        // Unsaved changes tracking
        this.trackUnsavedChanges();

        // Calculator events
        this.bindCalculatorEvents();

        // Keyboard shortcuts
        this.setupKeyboardShortcuts();
    }

    handleTypeChange(e) {
        this.state.currentType = e.target.value;
        this.toggleTypeSpecificFields();
        this.updateFormValidation();
        this.updateCalculations();
        this.showNotification(`Switched to ${this.state.currentType} mode`, 'info');
    }

    toggleTypeSpecificFields() {
        const isService = this.state.currentType === 'service';
        
        // Toggle service fields
        if (this.elements.serviceFields) {
            this.elements.serviceFields.style.display = isService ? 'block' : 'none';
            this.setRequiredFields(this.elements.serviceFields, isService);
        }

        // Toggle product-specific fields
        if (this.elements.productFields) {
            this.elements.productFields.style.display = !isService ? 'block' : 'none';
            this.setRequiredFields(this.elements.productFields, !isService);
        }

        // Update calculator visibility
        this.updateCalculatorVisibility();
        
        // Update form labels dynamically
        this.updateFormLabels();
    }

    handleBillingModelChange(e) {
        this.state.billingModel = e.target.value;
        this.toggleBillingFields();
        this.updateCalculations();
    }

    toggleBillingFields() {
        const isSubscription = this.state.billingModel === 'subscription';
        
        if (this.elements.subscriptionFields) {
            this.elements.subscriptionFields.style.display = isSubscription ? 'block' : 'none';
            this.setRequiredFields(this.elements.subscriptionFields, isSubscription);
        }

        // Update calculator sections
        const billingPeriodsSection = document.getElementById('billing-periods-section');
        const usageSection = document.getElementById('usage-amount-section');
        
        if (billingPeriodsSection) {
            billingPeriodsSection.style.display = isSubscription ? 'block' : 'none';
        }
        
        if (usageSection) {
            const isUsageBased = ['usage_based', 'hybrid'].includes(this.state.billingModel);
            usageSection.style.display = isUsageBased ? 'block' : 'none';
        }
    }

    handlePricingModelChange(e) {
        this.state.pricingModel = e.target.value;
        this.togglePricingModelFields();
        this.updateCalculations();
    }

    togglePricingModelFields() {
        // Hide all pricing model sections first
        ['tiered-pricing-section', 'volume-discount-section', 'usage-pricing-section'].forEach(id => {
            const section = document.getElementById(id);
            if (section) {
                section.style.display = 'none';
            }
        });

        // Show relevant section
        const currentSection = document.getElementById(`${this.state.pricingModel}-pricing-section`);
        if (currentSection) {
            currentSection.style.display = 'block';
        }

        // Special handling for tiered pricing
        if (this.state.pricingModel === 'tiered') {
            this.initializeTieredPricing();
        }
    }

    bindCalculatorEvents() {
        // Calculator input events
        ['calc-quantity', 'calc-billing-periods', 'calc-usage'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('input', () => this.updateCalculations());
            }
        });
    }

    updateCalculations() {
        const price = parseFloat(this.elements.basePrice?.value || 0);
        const cost = parseFloat(this.elements.cost?.value || 0);
        const quantity = parseInt(document.getElementById('calc-quantity')?.value || 1);
        const billingPeriods = parseInt(document.getElementById('calc-billing-periods')?.value || 1);
        const usage = parseFloat(document.getElementById('calc-usage')?.value || 0);

        let totalPrice = price * quantity;

        // Apply billing model calculations
        if (this.state.billingModel === 'subscription') {
            totalPrice *= billingPeriods;
        }

        if (['usage_based', 'hybrid'].includes(this.state.billingModel)) {
            totalPrice += (usage * price);
        }

        // Apply pricing model calculations
        if (this.state.pricingModel === 'tiered') {
            totalPrice = this.calculateTieredPrice(quantity, price);
        }

        // Update displays
        this.updatePriceDisplays(price, totalPrice, cost, quantity);
        this.updateProfitAnalysis(totalPrice, cost, quantity);
    }

    calculateTieredPrice(quantity, basePrice) {
        // Implement tiered pricing logic
        let totalPrice = 0;
        let remainingQuantity = quantity;

        this.state.tiers.forEach(tier => {
            const tierQuantity = Math.min(remainingQuantity, tier.maxQuantity - tier.minQuantity + 1);
            if (tierQuantity > 0) {
                totalPrice += tierQuantity * tier.price;
                remainingQuantity -= tierQuantity;
            }
        });

        return remainingQuantity > 0 ? totalPrice + (remainingQuantity * basePrice) : totalPrice;
    }

    updatePriceDisplays(basePrice, totalPrice, cost, quantity) {
        const basePriceEl = document.getElementById('calc-base-price');
        const totalPriceEl = document.getElementById('calc-total-price');

        if (basePriceEl) {
            basePriceEl.textContent = this.formatCurrency(basePrice);
        }

        if (totalPriceEl) {
            totalPriceEl.textContent = this.formatCurrency(totalPrice);
        }
    }

    updateProfitAnalysis(totalPrice, cost, quantity) {
        const profitSection = document.getElementById('profit-analysis');
        
        if (cost > 0 && profitSection) {
            const totalCost = cost * quantity;
            const profit = totalPrice - totalCost;
            const margin = totalPrice > 0 ? (profit / totalPrice * 100) : 0;

            document.getElementById('calc-total-cost').textContent = this.formatCurrency(totalCost);
            document.getElementById('calc-profit').textContent = this.formatCurrency(profit);
            document.getElementById('calc-margin').textContent = `${margin.toFixed(1)}%`;
            
            profitSection.style.display = 'block';
        } else if (profitSection) {
            profitSection.style.display = 'none';
        }
    }

    setupValidation() {
        // Real-time validation
        const inputs = document.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', (e) => this.validateField(e.target));
            input.addEventListener('input', (e) => this.clearFieldError(e.target));
        });
    }

    validateField(field) {
        const value = field.value.trim();
        const rules = this.getValidationRules(field);
        const errors = [];

        // Required validation
        if (field.hasAttribute('required') && !value) {
            errors.push(`${this.getFieldLabel(field)} is required`);
        }

        // Type-specific validations
        if (value) {
            if (field.type === 'email' && !this.isValidEmail(value)) {
                errors.push('Please enter a valid email address');
            }

            if (field.type === 'number') {
                const num = parseFloat(value);
                const min = parseFloat(field.getAttribute('min'));
                const max = parseFloat(field.getAttribute('max'));

                if (isNaN(num)) {
                    errors.push('Please enter a valid number');
                } else {
                    if (!isNaN(min) && num < min) {
                        errors.push(`Value must be at least ${min}`);
                    }
                    if (!isNaN(max) && num > max) {
                        errors.push(`Value must be no more than ${max}`);
                    }
                }
            }
        }

        // Business logic validations
        this.applyBusinessValidations(field, value, errors);

        // Update UI
        this.displayFieldErrors(field, errors);
        return errors.length === 0;
    }

    applyBusinessValidations(field, value, errors) {
        // Service-specific validations
        if (this.state.currentType === 'service') {
            if (field.name === 'billing_cycle' && this.state.billingModel === 'subscription' && !value) {
                errors.push('Billing cycle is required for subscription services');
            }
        }

        // Price validations
        if (field.name === 'base_price' || field.name === 'price') {
            const price = parseFloat(value);
            const cost = parseFloat(this.elements.cost?.value || 0);
            
            if (price > 0 && cost > 0 && price <= cost) {
                this.showWarning('Price is equal to or less than cost. This will result in no profit.');
            }
        }
    }

    displayFieldErrors(field, errors) {
        const feedbackEl = field.parentNode.querySelector('.validation-feedback');
        
        if (errors.length > 0) {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            
            if (feedbackEl) {
                feedbackEl.textContent = errors[0];
                feedbackEl.style.display = 'block';
                feedbackEl.className = 'validation-feedback invalid-feedback';
            }
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            
            if (feedbackEl) {
                feedbackEl.style.display = 'none';
            }
        }
    }

    clearFieldError(field) {
        field.classList.remove('is-invalid');
        const feedbackEl = field.parentNode.querySelector('.invalid-feedback');
        if (feedbackEl) {
            feedbackEl.style.display = 'none';
        }
    }

    initializeTieredPricing() {
        // Initialize with one tier if none exist
        if (this.state.tiers.length === 0) {
            this.addPricingTier();
        }
    }

    addPricingTier() {
        const tierId = `tier-${Date.now()}`;
        const tierIndex = this.state.tiers.length;
        
        const tierHtml = `
            <div class="pricing-tier border rounded p-3 mb-3" data-tier-id="${tierId}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Tier ${tierIndex + 1}</h6>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-tier" ${tierIndex === 0 ? 'disabled' : ''}>
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Min Quantity</label>
                        <input type="number" name="tiers[${tierIndex}][min_quantity]" 
                               class="form-control tier-min" value="${tierIndex === 0 ? 1 : ''}" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Max Quantity</label>
                        <input type="number" name="tiers[${tierIndex}][max_quantity]" 
                               class="form-control tier-max" min="1" placeholder="Unlimited">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Price per Unit</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="tiers[${tierIndex}][price]" 
                                   class="form-control tier-price" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Discount</label>
                        <div class="input-group">
                            <input type="number" class="form-control tier-discount" step="0.1" min="0" max="100" readonly>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const container = document.getElementById('pricing-tiers-container');
        if (container) {
            container.insertAdjacentHTML('beforeend', tierHtml);
            this.bindTierEvents(tierId);
            this.updateTierNumbers();
        }
    }

    bindTierEvents(tierId) {
        const tier = document.querySelector(`[data-tier-id="${tierId}"]`);
        if (!tier) return;

        // Remove tier event
        const removeBtn = tier.querySelector('.remove-tier');
        removeBtn?.addEventListener('click', () => this.removePricingTier(tierId));

        // Tier input events
        const inputs = tier.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', () => this.updateTierCalculations());
            input.addEventListener('blur', (e) => this.validateField(e.target));
        });
    }

    removePricingTier(tierId) {
        const tier = document.querySelector(`[data-tier-id="${tierId}"]`);
        if (tier) {
            tier.remove();
            this.updateTierNumbers();
            this.updateCalculations();
        }
    }

    updateTierNumbers() {
        const tiers = document.querySelectorAll('.pricing-tier');
        tiers.forEach((tier, index) => {
            const title = tier.querySelector('h6');
            if (title) {
                title.textContent = `Tier ${index + 1}`;
            }
            
            const removeBtn = tier.querySelector('.remove-tier');
            if (removeBtn) {
                removeBtn.disabled = index === 0;
            }
        });
    }

    updateTierCalculations() {
        const basePrice = parseFloat(this.elements.basePrice?.value || 0);
        const tiers = document.querySelectorAll('.pricing-tier');
        
        tiers.forEach(tier => {
            const priceInput = tier.querySelector('.tier-price');
            const discountDisplay = tier.querySelector('.tier-discount');
            
            if (priceInput && discountDisplay && basePrice > 0) {
                const tierPrice = parseFloat(priceInput.value || 0);
                const discount = ((basePrice - tierPrice) / basePrice) * 100;
                discountDisplay.value = discount > 0 ? discount.toFixed(1) : '0';
            }
        });
    }

    handleFormSubmission(e) {
        // Validate entire form
        const isValid = this.validateForm();
        
        if (!isValid) {
            e.preventDefault();
            this.showNotification('Please fix the errors below before submitting', 'error');
            this.scrollToFirstError();
            return false;
        }

        // Show loading state
        this.setLoadingState(true);
        
        // Allow submission to proceed
        return true;
    }

    validateForm() {
        const requiredFields = document.querySelectorAll('input[required], select[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    trackUnsavedChanges() {
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                this.state.unsavedChanges = true;
            });
        });

        // Warn before leaving with unsaved changes
        window.addEventListener('beforeunload', (e) => {
            if (this.state.unsavedChanges) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                this.elements.form?.submit();
            }

            // Ctrl/Cmd + Enter to submit
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                this.elements.form?.submit();
            }

            // Escape to clear current field
            if (e.key === 'Escape') {
                document.activeElement?.blur();
            }
        });
    }

    // Helper methods
    setRequiredFields(container, required) {
        const fields = container.querySelectorAll('input, select');
        fields.forEach(field => {
            if (required) {
                field.setAttribute('required', '');
            } else {
                field.removeAttribute('required');
            }
        });
    }

    updateCalculatorVisibility() {
        if (this.elements.pricingCalculator) {
            this.elements.pricingCalculator.style.display = 'block';
        }
    }

    updateFormLabels() {
        const typeLabel = this.state.currentType === 'service' ? 'Service' : 'Product';
        
        // Update form title
        const pageTitle = document.querySelector('h1, .page-title');
        if (pageTitle && pageTitle.textContent.includes('Product')) {
            pageTitle.textContent = pageTitle.textContent.replace('Product', typeLabel);
        }

        // Update submit button
        if (this.elements.submitButton) {
            const buttonText = this.elements.submitButton.textContent;
            this.elements.submitButton.textContent = buttonText.replace(/Product|Service/g, typeLabel);
        }
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    getFieldLabel(field) {
        const label = field.closest('.mb-3')?.querySelector('label');
        return label?.textContent.replace('*', '').trim() || field.name;
    }

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    setLoadingState(loading) {
        if (this.elements.submitButton) {
            this.elements.submitButton.disabled = loading;
            if (loading) {
                this.elements.submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            }
        }
    }

    scrollToFirstError() {
        const firstError = document.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
    }

    showNotification(message, type = 'info') {
        // Create or update notification
        let notification = document.getElementById('form-notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'form-notification';
            notification.className = 'alert alert-dismissible fade show position-fixed';
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
            document.body.appendChild(notification);
        }

        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Auto-hide after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 150);
        }, 5000);
    }

    showWarning(message) {
        this.showNotification(message, 'warning');
    }

    getValidationRules(field) {
        // Return validation rules for specific fields
        const rules = {};
        
        if (field.hasAttribute('required')) {
            rules.required = true;
        }
        
        if (field.type === 'email') {
            rules.email = true;
        }
        
        if (field.type === 'number') {
            rules.numeric = true;
            if (field.hasAttribute('min')) {
                rules.min = parseFloat(field.getAttribute('min'));
            }
            if (field.hasAttribute('max')) {
                rules.max = parseFloat(field.getAttribute('max'));
            }
        }
        
        return rules;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize on product/service create/edit pages
    if (document.querySelector('form[action*="products"], form[action*="services"]')) {
        window.productServiceBuilder = new ProductServiceBuilder();
    }
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProductServiceBuilder;
}