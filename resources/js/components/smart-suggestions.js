/**
 * Smart Template Suggestions Component
 * Provides intelligent template and pricing recommendations based on client history
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('smartSuggestions', (config = {}) => ({
        // Configuration
        maxSuggestions: config.maxSuggestions || 5,
        enableMachineLearning: config.enableMachineLearning !== false,
        cacheTimeout: config.cacheTimeout || 600000, // 10 minutes
        
        // State
        loading: false,
        suggestions: {
            templates: [],
            pricing: [],
            items: [],
            clients: []
        },
        
        // Current context
        currentClient: null,
        currentCategory: null,
        currentItems: [],
        
        // ML Models (simplified)
        models: {
            templateRecommendation: null,
            pricingOptimization: null,
            itemSuggestion: null
        },
        
        // Suggestion cache
        cache: new Map(),
        
        // Analytics data
        clientHistory: new Map(),
        templateUsage: new Map(),
        pricingPatterns: new Map(),
        
        // Confidence scores
        confidenceThresholds: {
            high: 0.8,
            medium: 0.6,
            low: 0.4
        },

        // Initialize smart suggestions
        init() {
            this.loadHistoricalData();
            this.initializeModels();
            this.setupEventListeners();
        },

        // Setup event listeners for context changes
        setupEventListeners() {
            // Listen for client selection
            document.addEventListener('client-selected', (e) => {
                this.updateClientContext(e.detail.client);
            });

            // Listen for category changes
            document.addEventListener('category-changed', (e) => {
                this.updateCategoryContext(e.detail.category);
            });

            // Listen for item changes
            document.addEventListener('items-updated', (e) => {
                this.updateItemsContext(e.detail.items);
            });

            // Watch for quote store changes
            this.$watch('$store.quote.document.client_id', (clientId) => {
                if (clientId) {
                    this.loadClientData(clientId);
                }
            });

            this.$watch('$store.quote.selectedItems', (items) => {
                this.updateItemsContext(items);
                this.generateItemSuggestions(items);
            }, { deep: true });
        },

        // Load historical data for ML models
        async loadHistoricalData() {
            try {
                this.loading = true;

                const [
                    clientHistoryData,
                    templateUsageData,
                    pricingData
                ] = await Promise.all([
                    this.fetchClientHistory(),
                    this.fetchTemplateUsage(),
                    this.fetchPricingPatterns()
                ]);

                this.processHistoricalData(clientHistoryData, templateUsageData, pricingData);

            } catch (error) {
                console.error('Failed to load historical data:', error);
            } finally {
                this.loading = false;
            }
        },

        // Fetch client history
        async fetchClientHistory() {
            const response = await fetch('/api/analytics/client-history');
            if (!response.ok) throw new Error('Failed to fetch client history');
            return response.json();
        },

        // Fetch template usage statistics
        async fetchTemplateUsage() {
            const response = await fetch('/api/analytics/template-usage');
            if (!response.ok) throw new Error('Failed to fetch template usage');
            return response.json();
        },

        // Fetch pricing patterns
        async fetchPricingPatterns() {
            const response = await fetch('/api/analytics/pricing-patterns');
            if (!response.ok) throw new Error('Failed to fetch pricing patterns');
            return response.json();
        },

        // Process historical data into ML-ready format
        processHistoricalData(clientHistory, templateUsage, pricingData) {
            // Process client history
            clientHistory.forEach(record => {
                const clientId = record.client_id;
                if (!this.clientHistory.has(clientId)) {
                    this.clientHistory.set(clientId, {
                        quotes: [],
                        preferences: {},
                        patterns: {}
                    });
                }

                const client = this.clientHistory.get(clientId);
                client.quotes.push({
                    template_id: record.template_id,
                    category_id: record.category_id,
                    total_amount: record.total_amount,
                    items: record.items,
                    created_at: record.created_at
                });
            });

            // Process template usage
            templateUsage.forEach(usage => {
                this.templateUsage.set(usage.template_id, {
                    usage_count: usage.usage_count,
                    success_rate: usage.success_rate,
                    avg_amount: usage.avg_amount,
                    client_types: usage.client_types
                });
            });

            // Process pricing patterns
            pricingData.forEach(pattern => {
                const key = `${pattern.category_id}_${pattern.item_type}`;
                this.pricingPatterns.set(key, {
                    avg_price: pattern.avg_price,
                    price_range: pattern.price_range,
                    frequency: pattern.frequency
                });
            });

            this.trainModels();
        },

        // Initialize ML models (simplified implementation)
        initializeModels() {
            // Simplified ML models using statistical analysis
            this.models.templateRecommendation = this.createTemplateModel();
            this.models.pricingOptimization = this.createPricingModel();
            this.models.itemSuggestion = this.createItemModel();
        },

        // Create template recommendation model
        createTemplateModel() {
            return {
                predict: (clientId, categoryId) => {
                    const clientData = this.clientHistory.get(clientId);
                    if (!clientData) return [];

                    // Calculate template scores based on client history
                    const templateScores = new Map();

                    clientData.quotes.forEach(quote => {
                        if (quote.category_id === categoryId) {
                            const score = templateScores.get(quote.template_id) || 0;
                            templateScores.set(quote.template_id, score + 1);
                        }
                    });

                    // Convert to recommendation list
                    return Array.from(templateScores.entries())
                        .map(([templateId, score]) => ({
                            template_id: templateId,
                            confidence: Math.min(score / clientData.quotes.length, 1),
                            reason: `Used ${score} times by this client`
                        }))
                        .sort((a, b) => b.confidence - a.confidence)
                        .slice(0, this.maxSuggestions);
                }
            };
        },

        // Create pricing optimization model
        createPricingModel() {
            return {
                predict: (items, clientId) => {
                    const suggestions = [];

                    items.forEach(item => {
                        const key = `${item.category_id || 'general'}_${item.type || 'product'}`;
                        const pattern = this.pricingPatterns.get(key);

                        if (pattern && item.unit_price) {
                            const deviation = Math.abs(item.unit_price - pattern.avg_price) / pattern.avg_price;
                            
                            if (deviation > 0.2) { // 20% deviation threshold
                                const suggestedPrice = pattern.avg_price;
                                const confidence = Math.max(0, 1 - deviation);

                                suggestions.push({
                                    item_id: item.id || item.temp_id,
                                    current_price: item.unit_price,
                                    suggested_price: suggestedPrice,
                                    confidence: confidence,
                                    reason: deviation > 0 ? 'Price above market average' : 'Price below market average',
                                    impact: this.calculatePriceImpact(item, suggestedPrice)
                                });
                            }
                        }
                    });

                    return suggestions.sort((a, b) => b.confidence - a.confidence);
                }
            };
        },

        // Create item suggestion model
        createItemModel() {
            return {
                predict: (currentItems, clientId, categoryId) => {
                    const clientData = this.clientHistory.get(clientId);
                    if (!clientData) return [];

                    // Find frequently bought together items
                    const itemCombinations = new Map();
                    const currentItemIds = currentItems.map(item => item.id || item.name);

                    clientData.quotes.forEach(quote => {
                        if (quote.category_id === categoryId) {
                            quote.items.forEach(item => {
                                if (!currentItemIds.includes(item.id) && !currentItemIds.includes(item.name)) {
                                    const count = itemCombinations.get(item.id) || 0;
                                    itemCombinations.set(item.id, count + 1);
                                }
                            });
                        }
                    });

                    return Array.from(itemCombinations.entries())
                        .map(([itemId, count]) => ({
                            item_id: itemId,
                            confidence: count / clientData.quotes.length,
                            reason: `Frequently purchased with similar items`,
                            frequency: count
                        }))
                        .filter(suggestion => suggestion.confidence >= this.confidenceThresholds.low)
                        .sort((a, b) => b.confidence - a.confidence)
                        .slice(0, this.maxSuggestions);
                }
            };
        },

        // Train models with current data
        trainModels() {
            // In a real implementation, this would involve actual ML training
            // For now, we update our statistical models
            this.updateStatisticalModels();
        },

        // Update statistical models
        updateStatisticalModels() {
            // Calculate global template success rates
            this.templateUsage.forEach((usage, templateId) => {
                usage.confidence_score = this.calculateTemplateConfidence(usage);
            });

            // Update pricing models
            this.pricingPatterns.forEach((pattern, key) => {
                pattern.confidence_score = this.calculatePricingConfidence(pattern);
            });
        },

        // Calculate template confidence score
        calculateTemplateConfidence(usage) {
            const usageWeight = Math.min(usage.usage_count / 100, 1) * 0.4;
            const successWeight = usage.success_rate * 0.6;
            return usageWeight + successWeight;
        },

        // Calculate pricing confidence score
        calculatePricingConfidence(pattern) {
            const frequencyWeight = Math.min(pattern.frequency / 50, 1) * 0.7;
            const consistencyWeight = (1 - pattern.price_range.std_dev / pattern.avg_price) * 0.3;
            return Math.max(0, frequencyWeight + consistencyWeight);
        },

        // Update client context
        async updateClientContext(client) {
            this.currentClient = client;
            
            if (client && client.id) {
                await this.loadClientData(client.id);
                this.generateTemplateSuggestions();
            }
        },

        // Update category context
        updateCategoryContext(category) {
            this.currentCategory = category;
            this.generateTemplateSuggestions();
        },

        // Update items context
        updateItemsContext(items) {
            this.currentItems = items;
            this.generatePricingSuggestions();
            this.generateItemSuggestions();
        },

        // Load specific client data
        async loadClientData(clientId) {
            const cacheKey = `client_${clientId}`;
            
            if (this.cache.has(cacheKey)) {
                const cached = this.cache.get(cacheKey);
                if (Date.now() - cached.timestamp < this.cacheTimeout) {
                    return cached.data;
                }
            }

            try {
                const response = await fetch(`/api/clients/${clientId}/analytics`);
                if (response.ok) {
                    const data = await response.json();
                    
                    this.cache.set(cacheKey, {
                        data,
                        timestamp: Date.now()
                    });

                    // Update client history
                    this.clientHistory.set(clientId, data);
                    
                    return data;
                }
            } catch (error) {
                console.error('Failed to load client data:', error);
            }
        },

        // Generate template suggestions
        async generateTemplateSuggestions() {
            if (!this.currentClient || !this.currentCategory) return;

            try {
                const predictions = this.models.templateRecommendation.predict(
                    this.currentClient.id,
                    this.currentCategory.id
                );

                // Enrich predictions with template details
                const enrichedSuggestions = await this.enrichTemplateSuggestions(predictions);
                
                this.suggestions.templates = enrichedSuggestions;

                // Dispatch event for UI updates
                this.$dispatch('suggestions-updated', {
                    type: 'templates',
                    suggestions: enrichedSuggestions
                });

            } catch (error) {
                console.error('Failed to generate template suggestions:', error);
            }
        },

        // Enrich template suggestions with details
        async enrichTemplateSuggestions(predictions) {
            const templateIds = predictions.map(p => p.template_id);
            
            try {
                const response = await fetch('/api/quote-templates/batch', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ template_ids: templateIds })
                });

                if (response.ok) {
                    const templates = await response.json();
                    
                    return predictions.map(prediction => {
                        const template = templates.find(t => t.id === prediction.template_id);
                        return {
                            ...prediction,
                            template: template,
                            confidence_level: this.getConfidenceLevel(prediction.confidence)
                        };
                    });
                }
            } catch (error) {
                console.error('Failed to enrich template suggestions:', error);
            }

            return predictions;
        },

        // Generate pricing suggestions
        generatePricingSuggestions() {
            if (!this.currentItems.length) return;

            const predictions = this.models.pricingOptimization.predict(
                this.currentItems,
                this.currentClient?.id
            );

            this.suggestions.pricing = predictions.map(prediction => ({
                ...prediction,
                confidence_level: this.getConfidenceLevel(prediction.confidence)
            }));

            this.$dispatch('suggestions-updated', {
                type: 'pricing',
                suggestions: this.suggestions.pricing
            });
        },

        // Generate item suggestions
        generateItemSuggestions() {
            if (!this.currentClient || !this.currentCategory || !this.currentItems.length) return;

            const predictions = this.models.itemSuggestion.predict(
                this.currentItems,
                this.currentClient.id,
                this.currentCategory.id
            );

            this.suggestions.items = predictions.map(prediction => ({
                ...prediction,
                confidence_level: this.getConfidenceLevel(prediction.confidence)
            }));

            this.$dispatch('suggestions-updated', {
                type: 'items',
                suggestions: this.suggestions.items
            });
        },

        // Calculate price impact
        calculatePriceImpact(item, suggestedPrice) {
            const currentTotal = item.unit_price * item.quantity;
            const suggestedTotal = suggestedPrice * item.quantity;
            const difference = suggestedTotal - currentTotal;
            
            return {
                amount_difference: difference,
                percentage_change: (difference / currentTotal) * 100,
                revenue_impact: difference > 0 ? 'increase' : 'decrease'
            };
        },

        // Get confidence level label
        getConfidenceLevel(confidence) {
            if (confidence >= this.confidenceThresholds.high) return 'high';
            if (confidence >= this.confidenceThresholds.medium) return 'medium';
            if (confidence >= this.confidenceThresholds.low) return 'low';
            return 'very_low';
        },

        // Apply template suggestion
        async applyTemplateSuggestion(suggestion) {
            try {
                const template = suggestion.template;
                
                // Load template into quote
                this.$store.quote.loadTemplate(template);
                
                // Track suggestion usage
                this.trackSuggestionUsage('template', suggestion);
                
                this.$dispatch('notification', {
                    type: 'success',
                    message: `Template "${template.name}" applied successfully`
                });

            } catch (error) {
                console.error('Failed to apply template suggestion:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Failed to apply template suggestion'
                });
            }
        },

        // Apply pricing suggestion
        applyPricingSuggestion(suggestion) {
            try {
                const items = this.$store.quote.selectedItems;
                const itemIndex = items.findIndex(item => 
                    (item.id || item.temp_id) === suggestion.item_id
                );

                if (itemIndex > -1) {
                    items[itemIndex].unit_price = suggestion.suggested_price;
                    this.$store.quote.recalculate();
                    
                    // Track suggestion usage
                    this.trackSuggestionUsage('pricing', suggestion);
                    
                    this.$dispatch('notification', {
                        type: 'success',
                        message: `Price updated to $${suggestion.suggested_price}`
                    });
                }

            } catch (error) {
                console.error('Failed to apply pricing suggestion:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Failed to apply pricing suggestion'
                });
            }
        },

        // Apply item suggestion
        async applyItemSuggestion(suggestion) {
            try {
                // Fetch item details
                const response = await fetch(`/api/items/${suggestion.item_id}`);
                if (response.ok) {
                    const item = await response.json();
                    
                    // Add item to quote
                    this.$store.quote.addItem(item);
                    
                    // Track suggestion usage
                    this.trackSuggestionUsage('item', suggestion);
                    
                    this.$dispatch('notification', {
                        type: 'success',
                        message: `Item "${item.name}" added to quote`
                    });
                }

            } catch (error) {
                console.error('Failed to apply item suggestion:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Failed to apply item suggestion'
                });
            }
        },

        // Track suggestion usage for ML improvement
        trackSuggestionUsage(type, suggestion) {
            const usage = {
                type,
                suggestion_id: suggestion.id || `${type}_${Date.now()}`,
                confidence: suggestion.confidence,
                client_id: this.currentClient?.id,
                category_id: this.currentCategory?.id,
                timestamp: new Date().toISOString()
            };

            // Send to analytics endpoint
            fetch('/api/analytics/suggestion-usage', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(usage)
            }).catch(error => {
                console.error('Failed to track suggestion usage:', error);
            });
        },

        // Dismiss suggestion
        dismissSuggestion(type, suggestionId) {
            this.suggestions[type] = this.suggestions[type].filter(s => 
                s.id !== suggestionId
            );

            // Track dismissal for ML improvement
            this.trackSuggestionDismissal(type, suggestionId);
        },

        // Track suggestion dismissal
        trackSuggestionDismissal(type, suggestionId) {
            fetch('/api/analytics/suggestion-dismissal', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type,
                    suggestion_id: suggestionId,
                    client_id: this.currentClient?.id,
                    timestamp: new Date().toISOString()
                })
            }).catch(error => {
                console.error('Failed to track suggestion dismissal:', error);
            });
        },

        // Get suggestions summary
        getSuggestionsSummary() {
            const total = Object.values(this.suggestions).reduce((sum, suggestions) => 
                sum + suggestions.length, 0
            );

            const highConfidence = Object.values(this.suggestions).reduce((sum, suggestions) => 
                sum + suggestions.filter(s => s.confidence_level === 'high').length, 0
            );

            return {
                total,
                high_confidence: highConfidence,
                has_suggestions: total > 0
            };
        },

        // Computed properties
        get hasTemplateSuggestions() {
            return this.suggestions.templates.length > 0;
        },

        get hasPricingSuggestions() {
            return this.suggestions.pricing.length > 0;
        },

        get hasItemSuggestions() {
            return this.suggestions.items.length > 0;
        },

        get totalSuggestions() {
            return Object.values(this.suggestions).reduce((sum, suggestions) => 
                sum + suggestions.length, 0
            );
        }
    }));
});