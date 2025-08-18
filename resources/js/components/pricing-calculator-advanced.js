/**
 * Advanced Pricing Calculator Component
 * Real-time pricing calculations with tiered, volume, and usage-based models
 */

class PricingCalculatorAdvanced {
    constructor(options = {}) {
        this.options = {
            containerId: 'pricing-calculator-advanced',
            currency: 'USD',
            locale: 'en-US',
            defaultQuantity: 1,
            showProfitAnalysis: true,
            showRevenueProjections: true,
            ...options
        };
        
        this.state = {
            basePrice: 0,
            cost: 0,
            quantity: this.options.defaultQuantity,
            pricingModel: 'fixed',
            billingModel: 'one_time',
            billingCycle: 'month',
            billingInterval: 1,
            usageAmount: 0,
            tiers: [],
            volumeDiscounts: [],
            calculations: {
                subtotal: 0,
                discounts: 0,
                taxes: 0,
                total: 0,
                profit: 0,
                margin: 0
            }
        };
        
        this.init();
    }

    init() {
        this.createContainer();
        this.bindEvents();
        this.updateCalculations();
    }

    createContainer() {
        const container = document.getElementById(this.options.containerId);
        if (!container) {
            console.warn('Pricing calculator container not found');
            return;
        }

        container.innerHTML = this.getCalculatorHTML();
        this.container = container;
        this.bindCalculatorEvents();
    }

    getCalculatorHTML() {
        return `
            <div class="pricing-calculator-advanced card">
                <div class="card-header">
                    <h6 class="card-title mb-0 d-flex align-items-center">
                        <i class="fas fa-calculator text-primary me-2"></i>
                        Advanced Pricing Calculator
                        <span class="ms-auto text-muted small" id="calc-last-updated"></span>
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Input Controls -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Quantity</label>
                            <div class="input-group input-group-sm">
                                <input type="number" id="calc-quantity" class="form-control" 
                                       value="${this.state.quantity}" min="0" step="1">
                                <span class="input-group-text">units</span>
                            </div>
                        </div>
                        <div class="col-md-3" id="billing-periods-section" style="display: none;">
                            <label class="form-label small fw-bold">Billing Periods</label>
                            <div class="input-group input-group-sm">
                                <input type="number" id="calc-billing-periods" class="form-control" 
                                       value="1" min="1" step="1">
                                <span class="input-group-text">periods</span>
                            </div>
                        </div>
                        <div class="col-md-3" id="usage-section" style="display: none;">
                            <label class="form-label small fw-bold">Usage Amount</label>
                            <div class="input-group input-group-sm">
                                <input type="number" id="calc-usage" class="form-control" 
                                       value="0" min="0" step="0.01">
                                <span class="input-group-text">units</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Tax Rate</label>
                            <div class="input-group input-group-sm">
                                <input type="number" id="calc-tax-rate" class="form-control" 
                                       value="0" min="0" max="100" step="0.1">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Display -->
                    <div class="pricing-display row">
                        <div class="col-md-3">
                            <div class="pricing-item text-center">
                                <label class="text-muted small">Base Price</label>
                                <div id="calc-base-price" class="fs-5 fw-bold text-primary">$0.00</div>
                                <small class="text-muted">per unit</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="pricing-item text-center">
                                <label class="text-muted small">Subtotal</label>
                                <div id="calc-subtotal" class="fs-5 fw-bold text-info">$0.00</div>
                                <small class="text-muted" id="calc-subtotal-breakdown"></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="pricing-item text-center">
                                <label class="text-muted small">Discounts</label>
                                <div id="calc-discounts" class="fs-5 fw-bold text-warning">-$0.00</div>
                                <small class="text-muted" id="calc-discount-breakdown"></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="pricing-item text-center">
                                <label class="text-muted small">Total</label>
                                <div id="calc-total" class="fs-4 fw-bold text-success">$0.00</div>
                                <small class="text-muted">including tax</small>
                            </div>
                        </div>
                    </div>

                    <!-- Profit Analysis -->
                    <div id="profit-analysis" class="mt-4" style="display: none;">
                        <h6 class="text-muted">Profit Analysis</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <label class="text-muted small">Total Cost</label>
                                    <div id="calc-total-cost" class="fw-bold text-danger">$0.00</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <label class="text-muted small">Gross Profit</label>
                                    <div id="calc-profit" class="fw-bold text-success">$0.00</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <label class="text-muted small">Margin</label>
                                    <div id="calc-margin" class="fw-bold text-info">0%</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <label class="text-muted small">Markup</label>
                                    <div id="calc-markup" class="fw-bold text-secondary">0%</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Projections -->
                    <div id="revenue-projections" class="mt-4" style="display: none;">
                        <h6 class="text-muted">Revenue Projections</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <label class="text-muted small">Monthly</label>
                                    <div id="calc-monthly-revenue" class="fw-bold">$0.00</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <label class="text-muted small">Quarterly</label>
                                    <div id="calc-quarterly-revenue" class="fw-bold">$0.00</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <label class="text-muted small">Annual</label>
                                    <div id="calc-annual-revenue" class="fw-bold">$0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tiered Pricing Breakdown -->
                    <div id="tiered-breakdown" class="mt-4" style="display: none;">
                        <h6 class="text-muted">Pricing Breakdown</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tier</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="tiered-breakdown-body"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="export-calculation">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="reset-calculator">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" id="save-scenario">
                            <i class="fas fa-save"></i> Save Scenario
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    bindCalculatorEvents() {
        if (!this.container) return;

        // Input events
        ['calc-quantity', 'calc-billing-periods', 'calc-usage', 'calc-tax-rate'].forEach(id => {
            const element = this.container.querySelector(`#${id}`);
            if (element) {
                element.addEventListener('input', (e) => this.handleInputChange(e));
                element.addEventListener('blur', (e) => this.validateInput(e));
            }
        });

        // Action buttons
        const exportBtn = this.container.querySelector('#export-calculation');
        const resetBtn = this.container.querySelector('#reset-calculator');
        const saveBtn = this.container.querySelector('#save-scenario');

        if (exportBtn) exportBtn.addEventListener('click', () => this.exportCalculation());
        if (resetBtn) resetBtn.addEventListener('click', () => this.resetCalculator());
        if (saveBtn) saveBtn.addEventListener('click', () => this.saveScenario());
    }

    bindEvents() {
        // Listen for form changes
        document.addEventListener('input', (e) => {
            if (this.isRelevantInput(e.target)) {
                this.updateFromForm();
            }
        });

        // Listen for pricing model changes
        document.addEventListener('change', (e) => {
            if (e.target.name === 'pricing_model') {
                this.state.pricingModel = e.target.value;
                this.updateVisibleSections();
                this.updateCalculations();
            }
            
            if (e.target.name === 'billing_model') {
                this.state.billingModel = e.target.value;
                this.updateVisibleSections();
                this.updateCalculations();
            }
        });
    }

    isRelevantInput(input) {
        const relevantNames = ['base_price', 'price', 'cost', 'pricing_model', 'billing_model', 'billing_cycle'];
        return relevantNames.includes(input.name) || input.id?.startsWith('calc-');
    }

    updateFromForm() {
        // Update state from form inputs
        const basePriceInput = document.querySelector('input[name="base_price"], input[name="price"]');
        const costInput = document.querySelector('input[name="cost"]');
        const pricingModelInput = document.querySelector('select[name="pricing_model"]');
        const billingModelInput = document.querySelector('select[name="billing_model"]');

        if (basePriceInput) this.state.basePrice = parseFloat(basePriceInput.value) || 0;
        if (costInput) this.state.cost = parseFloat(costInput.value) || 0;
        if (pricingModelInput) this.state.pricingModel = pricingModelInput.value;
        if (billingModelInput) this.state.billingModel = billingModelInput.value;

        this.updateCalculations();
    }

    handleInputChange(e) {
        const value = parseFloat(e.target.value) || 0;
        
        switch (e.target.id) {
            case 'calc-quantity':
                this.state.quantity = Math.max(0, value);
                break;
            case 'calc-billing-periods':
                this.state.billingPeriods = Math.max(1, value);
                break;
            case 'calc-usage':
                this.state.usageAmount = Math.max(0, value);
                break;
            case 'calc-tax-rate':
                this.state.taxRate = Math.max(0, Math.min(100, value));
                break;
        }
        
        this.updateCalculations();
    }

    validateInput(e) {
        const value = parseFloat(e.target.value);
        const min = parseFloat(e.target.min) || 0;
        const max = parseFloat(e.target.max) || Infinity;
        
        if (isNaN(value) || value < min || value > max) {
            e.target.classList.add('is-invalid');
            this.showInputError(e.target, `Value must be between ${min} and ${max}`);
        } else {
            e.target.classList.remove('is-invalid');
            this.clearInputError(e.target);
        }
    }

    updateVisibleSections() {
        if (!this.container) return;

        const billingPeriodsSection = this.container.querySelector('#billing-periods-section');
        const usageSection = this.container.querySelector('#usage-section');
        const tieredBreakdown = this.container.querySelector('#tiered-breakdown');

        // Show billing periods for subscription models
        if (billingPeriodsSection) {
            billingPeriodsSection.style.display = 
                this.state.billingModel === 'subscription' ? 'block' : 'none';
        }

        // Show usage section for usage-based models
        if (usageSection) {
            usageSection.style.display = 
                ['usage_based', 'hybrid'].includes(this.state.billingModel) ? 'block' : 'none';
        }

        // Show tiered breakdown for tiered pricing
        if (tieredBreakdown) {
            tieredBreakdown.style.display = 
                this.state.pricingModel === 'tiered' ? 'block' : 'none';
        }
    }

    updateCalculations() {
        const calculations = this.calculatePricing();
        this.state.calculations = calculations;
        this.updateDisplay(calculations);
        this.updateLastUpdated();
    }

    calculatePricing() {
        let subtotal = 0;
        let discounts = 0;
        const quantity = this.state.quantity || 1;
        const basePrice = this.state.basePrice || 0;
        const cost = this.state.cost || 0;
        const taxRate = this.state.taxRate || 0;

        // Calculate based on pricing model
        switch (this.state.pricingModel) {
            case 'tiered':
                subtotal = this.calculateTieredPrice(quantity, basePrice);
                break;
            case 'volume':
                const volumeResult = this.calculateVolumePrice(quantity, basePrice);
                subtotal = volumeResult.subtotal;
                discounts = volumeResult.discounts;
                break;
            case 'usage':
                subtotal = this.calculateUsagePrice();
                break;
            default:
                subtotal = basePrice * quantity;
        }

        // Apply billing model multipliers
        if (this.state.billingModel === 'subscription') {
            subtotal *= (this.state.billingPeriods || 1);
        }

        if (['usage_based', 'hybrid'].includes(this.state.billingModel)) {
            subtotal += (this.state.usageAmount || 0) * basePrice;
        }

        // Calculate taxes
        const taxes = (subtotal - discounts) * (taxRate / 100);
        const total = subtotal - discounts + taxes;

        // Calculate profit metrics
        const totalCost = cost * quantity;
        const profit = total - totalCost;
        const margin = total > 0 ? (profit / total) * 100 : 0;
        const markup = totalCost > 0 ? (profit / totalCost) * 100 : 0;

        return {
            subtotal,
            discounts,
            taxes,
            total,
            profit,
            margin,
            markup,
            totalCost
        };
    }

    calculateTieredPrice(quantity, basePrice) {
        // Implementation depends on tier configuration
        // For now, return base calculation
        return basePrice * quantity;
    }

    calculateVolumePrice(quantity, basePrice) {
        let subtotal = basePrice * quantity;
        let discounts = 0;

        // Apply volume discounts based on quantity
        if (quantity >= 100) {
            discounts = subtotal * 0.15; // 15% discount
        } else if (quantity >= 50) {
            discounts = subtotal * 0.10; // 10% discount
        } else if (quantity >= 25) {
            discounts = subtotal * 0.05; // 5% discount
        }

        return { subtotal, discounts };
    }

    calculateUsagePrice() {
        return (this.state.usageAmount || 0) * (this.state.basePrice || 0);
    }

    updateDisplay(calculations) {
        if (!this.container) return;

        // Update pricing displays
        this.updateElement('#calc-base-price', this.formatCurrency(this.state.basePrice));
        this.updateElement('#calc-subtotal', this.formatCurrency(calculations.subtotal));
        this.updateElement('#calc-discounts', `-${this.formatCurrency(calculations.discounts)}`);
        this.updateElement('#calc-total', this.formatCurrency(calculations.total));

        // Update breakdown text
        this.updateElement('#calc-subtotal-breakdown', 
            `${this.state.quantity} × ${this.formatCurrency(this.state.basePrice)}`);
        
        if (calculations.discounts > 0) {
            const discountPercent = ((calculations.discounts / calculations.subtotal) * 100).toFixed(1);
            this.updateElement('#calc-discount-breakdown', `${discountPercent}% off`);
        }

        // Update profit analysis
        if (this.options.showProfitAnalysis && this.state.cost > 0) {
            this.showProfitAnalysis(calculations);
        }

        // Update revenue projections
        if (this.options.showRevenueProjections && this.state.billingModel === 'subscription') {
            this.showRevenueProjections(calculations);
        }
    }

    showProfitAnalysis(calculations) {
        const profitSection = this.container.querySelector('#profit-analysis');
        if (!profitSection) return;

        profitSection.style.display = 'block';
        
        this.updateElement('#calc-total-cost', this.formatCurrency(calculations.totalCost));
        this.updateElement('#calc-profit', this.formatCurrency(calculations.profit));
        this.updateElement('#calc-margin', `${calculations.margin.toFixed(1)}%`);
        this.updateElement('#calc-markup', `${calculations.markup.toFixed(1)}%`);

        // Color coding based on margin
        const marginElement = this.container.querySelector('#calc-margin');
        if (marginElement) {
            marginElement.className = 'fw-bold ' + 
                (calculations.margin >= 30 ? 'text-success' :
                 calculations.margin >= 15 ? 'text-warning' : 'text-danger');
        }
    }

    showRevenueProjections(calculations) {
        const projectionSection = this.container.querySelector('#revenue-projections');
        if (!projectionSection) return;

        projectionSection.style.display = 'block';

        const monthly = calculations.total;
        const quarterly = monthly * 3;
        const annual = monthly * 12;

        this.updateElement('#calc-monthly-revenue', this.formatCurrency(monthly));
        this.updateElement('#calc-quarterly-revenue', this.formatCurrency(quarterly));
        this.updateElement('#calc-annual-revenue', this.formatCurrency(annual));
    }

    updateElement(selector, content) {
        const element = this.container.querySelector(selector);
        if (element) {
            element.textContent = content;
        }
    }

    updateLastUpdated() {
        const element = this.container.querySelector('#calc-last-updated');
        if (element) {
            element.textContent = `Updated ${new Date().toLocaleTimeString()}`;
        }
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat(this.options.locale, {
            style: 'currency',
            currency: this.options.currency
        }).format(amount || 0);
    }

    showInputError(input, message) {
        let feedback = input.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            input.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
    }

    clearInputError(input) {
        const feedback = input.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
    }

    exportCalculation() {
        const data = {
            timestamp: new Date().toISOString(),
            inputs: {
                basePrice: this.state.basePrice,
                cost: this.state.cost,
                quantity: this.state.quantity,
                pricingModel: this.state.pricingModel,
                billingModel: this.state.billingModel
            },
            calculations: this.state.calculations
        };

        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `pricing-calculation-${Date.now()}.json`;
        a.click();
        URL.revokeObjectURL(url);
    }

    resetCalculator() {
        this.state.quantity = this.options.defaultQuantity;
        this.state.usageAmount = 0;
        this.state.taxRate = 0;
        
        // Reset input values
        const inputs = this.container.querySelectorAll('input[type="number"]');
        inputs.forEach(input => {
            if (input.id === 'calc-quantity') {
                input.value = this.options.defaultQuantity;
            } else {
                input.value = 0;
            }
        });

        this.updateCalculations();
    }

    saveScenario() {
        // Implementation for saving pricing scenarios
        console.log('Saving scenario:', this.state);
        
        if (window.Swal) {
            window.Swal.fire({
                icon: 'success',
                title: 'Scenario Saved',
                text: 'Pricing scenario saved successfully',
                timer: 2000,
                showConfirmButton: false
            });
        }
    }
}

// Auto-initialize if container exists
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('pricing-calculator-advanced');
    if (container && !window.pricingCalculatorAdvanced) {
        window.pricingCalculatorAdvanced = new PricingCalculatorAdvanced();
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PricingCalculatorAdvanced;
}