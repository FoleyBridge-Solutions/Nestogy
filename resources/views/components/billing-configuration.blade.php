{{-- Billing Configuration Component --}}
<div x-data="{
    billingOptions: {
        model: 'one_time',
        cycle: 'monthly',
        paymentTerms: 30,
        startDate: new Date().toISOString().split('T')[0],
        endDate: '',
        autoRenew: false,
        lateFeePercentage: 0,
        earlyPaymentDiscount: 0
    },
    subscription: {
        trialDays: 0,
        setupFee: 0,
        minimumTerm: 0,
        gracePeriodDays: 7
    },
    paymentMethods: {
        creditCard: true,
        ach: false,
        check: false,
        wire: false
    },
    
    init() {
        // Watch for changes and dispatch event
        this.$watch('billingOptions', () => {
            this.emitConfiguration();
        }, { deep: true });
        
        this.$watch('subscription', () => {
            this.emitConfiguration();
        }, { deep: true });
    },
    
    setBillingModel(model) {
        this.billingOptions.model = model;
        this.emitConfiguration();
    },
    
    emitConfiguration() {
        const config = {
            billing_options: this.billingOptions,
            subscription: this.subscription,
            payment_methods: this.paymentMethods
        };
        
        this.$dispatch('billing-configured', { configuration: config });
        
        // Update root for other components
        if (this.$root) {
            this.$root.billingConfig = config;
        }
    }
}"
class="billing-configuration">
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h6 class="mb-0">
                <i class="fas fa-cog mr-2"></i>Billing Configuration
            </h6>
        </div>
        
        <div class="p-6">
            <!-- Billing Model -->
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Billing Model</label>
                <div class="btn-group w-100" role="group">
                    <button type="button"
                            @click="setBillingModel('one_time')"
                            class="btn"
                            :class="billingOptions.model === 'one_time' ? 'btn-primary' : 'btn-outline-primary'">
                        <i class="fas fa-shopping-cart"></i> One-time
                    </button>
                    <button type="button"
                            @click="setBillingModel('subscription')"
                            class="btn"
                            :class="billingOptions.model === 'subscription' ? 'btn-primary' : 'btn-outline-primary'">
                        <i class="fas fa-sync"></i> Subscription
                    </button>
                    <button type="button"
                            @click="setBillingModel('usage_based')"
                            class="btn"
                            :class="billingOptions.model === 'usage_based' ? 'btn-primary' : 'btn-outline-primary'">
                        <i class="fas fa-tachometer-alt"></i> Usage-based
                    </button>
                </div>
            </div>
            
            <!-- Billing Cycle (for subscription/usage-based) -->
            <div class="mb-3" x-show="billingOptions.model !== 'one_time'">
                <label class="block text-sm font-medium text-gray-700 mb-1">Billing Cycle</label>
                <select x-model="billingOptions.cycle" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="semi_annually">Semi-Annually</option>
                    <option value="annually">Annually</option>
                </select>
            </div>
            
            <!-- Date Range -->
            <div class="flex flex-wrap -mx-4">
                <div class="md:w-1/2 px-4 mb-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" 
                           x-model="billingOptions.startDate" 
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div class="md:w-1/2 px-4 mb-3" x-show="billingOptions.model !== 'one_time'">
                    <label class="form-label">End Date (Optional)</label>
                    <input type="date" 
                           x-model="billingOptions.endDate" 
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="No end date">
                </div>
            </div>
            
            <!-- Payment Terms -->
            <div class="mb-3">
                <label class="form-label">Payment Terms (Days)</label>
                <select x-model="billingOptions.paymentTerms" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="0">Due on Receipt</option>
                    <option value="15">Net 15</option>
                    <option value="30">Net 30</option>
                    <option value="45">Net 45</option>
                    <option value="60">Net 60</option>
                    <option value="90">Net 90</option>
                </select>
            </div>
            
            <!-- Subscription Settings (if subscription) -->
            <div x-show="billingOptions.model === 'subscription'" class="border-top pt-3 mt-3">
                <h6 class="mb-3">Subscription Settings</h6>
                
                <div class="flex flex-wrap -mx-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Trial Period (Days)</label>
                        <input type="number" 
                               x-model="subscription.trialDays" 
                               class="form-control" 
                               min="0"
                               placeholder="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Setup Fee</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" 
                                   x-model="subscription.setupFee" 
                                   class="form-control" 
                                   min="0" 
                                   step="0.01"
                                   placeholder="0.00">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Minimum Term (Months)</label>
                        <input type="number" 
                               x-model="subscription.minimumTerm" 
                               class="form-control" 
                               min="0"
                               placeholder="No minimum">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Grace Period (Days)</label>
                        <input type="number" 
                               x-model="subscription.gracePeriodDays" 
                               class="form-control" 
                               min="0"
                               placeholder="7">
                    </div>
                </div>
                
                <!-- Auto-renewal -->
                <div class="form-check">
                    <input type="checkbox" 
                           x-model="billingOptions.autoRenew" 
                           class="form-check-input" 
                           id="autoRenew">
                    <label class="form-check-label" for="autoRenew">
                        Enable Auto-renewal
                    </label>
                </div>
            </div>
            
            <!-- Late Fees & Early Payment -->
            <div class="border-top pt-3 mt-3">
                <h6 class="mb-3">Fees & Discounts</h6>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Late Fee (%)</label>
                        <div class="input-group">
                            <input type="number" 
                                   x-model="billingOptions.lateFeePercentage" 
                                   class="form-control" 
                                   min="0" 
                                   max="100" 
                                   step="0.01"
                                   placeholder="0">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Early Payment Discount (%)</label>
                        <div class="input-group">
                            <input type="number" 
                                   x-model="billingOptions.earlyPaymentDiscount" 
                                   class="form-control" 
                                   min="0" 
                                   max="100" 
                                   step="0.01"
                                   placeholder="0">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <div class="border-top pt-3 mt-3">
                <h6 class="mb-3">Accepted Payment Methods</h6>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-2">
                            <input type="checkbox" 
                                   x-model="paymentMethods.creditCard" 
                                   class="form-check-input" 
                                   id="pmCreditCard">
                            <label class="form-check-label" for="pmCreditCard">
                                <i class="fas fa-credit-bg-white rounded-lg shadow-md overflow-hidden"></i> Credit/Debit Card
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input type="checkbox" 
                                   x-model="paymentMethods.ach" 
                                   class="form-check-input" 
                                   id="pmACH">
                            <label class="form-check-label" for="pmACH">
                                <i class="fas fa-university"></i> ACH Transfer
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-check mb-2">
                            <input type="checkbox" 
                                   x-model="paymentMethods.check" 
                                   class="form-check-input" 
                                   id="pmCheck">
                            <label class="form-check-label" for="pmCheck">
                                <i class="fas fa-money-check"></i> Check
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input type="checkbox" 
                                   x-model="paymentMethods.wire" 
                                   class="form-check-input" 
                                   id="pmWire">
                            <label class="form-check-label" for="pmWire">
                                <i class="fas fa-exchange-alt"></i> Wire Transfer
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Summary -->
        <div class="card-footer bg-gray-100">
            <div class="flex justify-between items-center">
                <div>
                    <small class="text-gray-600">
                        Model: <strong x-text="billingOptions.model.replace('_', ' ')"></strong>
                        <span x-show="billingOptions.model !== 'one_time'">
                            | Cycle: <strong x-text="billingOptions.cycle"></strong>
                        </span>
                        | Terms: <strong x-text="billingOptions.paymentTerms + ' days'"></strong>
                    </small>
                </div>
                <button @click="emitConfiguration()" class="btn btn-sm btn-primary">
                    <i class="fas fa-check"></i> Apply
                </button>
            </div>
        </div>
    </div>
</div>