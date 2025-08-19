<?php

namespace App\Domains\Financial\Requests;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Contract::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where('company_id', $this->user()->company_id)
            ],
            'quote_id' => [
                'nullable',
                'integer',
                Rule::exists('quotes', 'id')->where('company_id', $this->user()->company_id)
            ],
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('contract_templates', 'id')->where('company_id', $this->user()->company_id)
            ],
            'contract_type' => [
                'required',
                'string',
Rule::in(['one_time_service', 'recurring_service', 'maintenance', 'support', 'managed_services'])
            ],
            'status' => [
                'sometimes',
                'string',
Rule::in([
                    Contract::STATUS_DRAFT,
                    Contract::STATUS_PENDING_REVIEW,
                ])
            ],
            'signature_status' => [
                'sometimes',
                'string',
                Rule::in([
                    Contract::SIGNATURE_NOT_REQUIRED,
                    Contract::SIGNATURE_PENDING,
                ])
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'term_months' => 'nullable|integer|min:1|max:120',
            'renewal_type' => [
                'nullable',
                'string',
                Rule::in(['none', 'manual', 'automatic', 'negotiated'])
            ],
            'renewal_notice_days' => 'nullable|integer|min:1|max:365',
            'auto_renewal' => 'boolean',
            'currency_code' => 'required|string|size:3|in:USD,EUR,GBP,CAD,AUD,JPY',
            'payment_terms' => 'nullable|string|max:1000',
            'pricing_structure' => 'nullable|array',
            'pricing_structure.recurring_monthly' => 'nullable|numeric|min:0',
            'pricing_structure.one_time' => 'nullable|numeric|min:0',
            'pricing_structure.setup_fee' => 'nullable|numeric|min:0',
            'pricing_structure.renewal_adjustment' => 'nullable|array',
            'sla_terms' => 'nullable|array',
            'sla_terms.response_time_hours' => 'nullable|numeric|min:0.1|max:72',
            'sla_terms.resolution_time_hours' => 'nullable|numeric|min:0.1|max:720',
            'sla_terms.uptime_percentage' => 'nullable|numeric|min:90|max:100',
            'voip_specifications' => 'nullable|array',
            'voip_specifications.services' => 'nullable|array',
            'voip_specifications.equipment' => 'nullable|array',
            'voip_specifications.phone_numbers' => 'nullable|integer|min:0',
            'compliance_requirements' => 'nullable|array',
            'terms_and_conditions' => 'nullable|string',
            'custom_clauses' => 'nullable|array',
            'termination_clause' => 'nullable|string',
            'liability_clause' => 'nullable|string',
            'confidentiality_clause' => 'nullable|string',
            'dispute_resolution' => 'nullable|string|max:1000',
            'governing_law' => 'nullable|string|max:255',
            'jurisdiction' => 'nullable|string|max:255',
            'milestones' => 'nullable|array',
            'milestones.*.title' => 'required_with:milestones|string|max:255',
            'milestones.*.description' => 'nullable|string|max:1000',
            'milestones.*.due_date' => 'required_with:milestones|date|after:start_date',
            'milestones.*.value' => 'nullable|numeric|min:0',
            'deliverables' => 'nullable|array',
            'deliverables.*.title' => 'required_with:deliverables|string|max:255',
            'deliverables.*.description' => 'nullable|string|max:1000',
            'deliverables.*.due_date' => 'required_with:deliverables|date|after:start_date',
            'penalties' => 'nullable|array',
            'penalties.*.type' => 'required_with:penalties|string|max:100',
            'penalties.*.amount' => 'required_with:penalties|numeric|min:0',
            'penalties.*.description' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
            
            // Schedule Configuration Data
            'variable_values' => 'nullable|json',
            'billing_config' => 'nullable|json',
            'infrastructure_schedule' => 'nullable|json',
            'pricing_schedule' => 'nullable|json', 
            'additional_terms' => 'nullable|json',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'client_id.exists' => 'The selected client does not exist or you do not have access to it.',
            'quote_id.exists' => 'The selected quote does not exist or you do not have access to it.',
            'template_id.exists' => 'The selected template does not exist or you do not have access to it.',
            'contract_type.in' => 'The selected contract type is invalid.',
            'start_date.after_or_equal' => 'The start date must be today or later.',
            'end_date.after' => 'The end date must be after the start date.',
            'term_months.max' => 'The term cannot exceed 120 months (10 years).',
            'renewal_notice_days.max' => 'The renewal notice period cannot exceed 365 days.',
            'currency_code.size' => 'The currency code must be exactly 3 characters.',
            'currency_code.in' => 'The currency code must be one of: USD, EUR, GBP, CAD, AUD, JPY.',
            'sla_terms.response_time_hours.max' => 'Response time cannot exceed 72 hours.',
            'sla_terms.resolution_time_hours.max' => 'Resolution time cannot exceed 720 hours (30 days).',
            'sla_terms.uptime_percentage.min' => 'Uptime percentage must be at least 90%.',
            'milestones.*.due_date.after' => 'Milestone due dates must be after the contract start date.',
            'deliverables.*.due_date.after' => 'Deliverable due dates must be after the contract start date.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Set default contract_value to 0 - will be calculated after creation
            $this->merge(['contract_value' => 0]);
            
            // Validate that end_date is required if term_months is not provided
            if (!$this->end_date && !$this->term_months) {
                $validator->errors()->add('end_date', 'Either end date or term in months must be provided.');
                $validator->errors()->add('term_months', 'Either end date or term in months must be provided.');
            }

            // Validate that auto_renewal requires renewal_notice_days
            if ($this->auto_renewal && !$this->renewal_notice_days) {
                $validator->errors()->add('renewal_notice_days', 'Renewal notice days is required when auto renewal is enabled.');
            }

            // Template-specific validation
            $this->validateTemplateSpecificRequirements($validator);
        });
    }

    /**
     * Apply template-specific validation rules
     */
    protected function validateTemplateSpecificRequirements($validator): void
    {
        $templateId = $this->input('template_id');
        
        if (!$templateId) {
            return; // No template selected, use base validation only
        }

        try {
            $template = \App\Models\ContractTemplate::where('company_id', $this->user()->company_id)
                ->findOrFail($templateId);
            
            $templateType = $template->type ?? 'infrastructure';
            
            // Apply validation based on template type
            switch ($templateType) {
                case 'sip_trunking':
                case 'unified_communications':
                case 'international_calling':
                    $this->validateTelecomTemplate($validator);
                    break;
                    
                case 'hardware_procurement':
                case 'equipment_leasing':
                case 'installation_services':
                    $this->validateHardwareTemplate($validator);
                    break;
                    
                case 'hipaa_compliance':
                case 'sox_compliance':
                case 'pci_compliance':
                    $this->validateComplianceTemplate($validator);
                    break;
                    
                default:
                    $this->validateInfrastructureTemplate($validator);
                    break;
            }
            
        } catch (\Exception $e) {
            // Template not found or invalid, skip template-specific validation
        }
    }

    /**
     * Validate telecom template requirements
     */
    protected function validateTelecomTemplate($validator): void
    {
        $pricingSchedule = $this->getParsedPricingSchedule();
        
        // Telecom templates should have telecom pricing
        if (empty($pricingSchedule['telecomPricing'])) {
            $validator->errors()->add('pricing_schedule', 'Telecom pricing configuration is required for telecommunications contracts.');
        }
        
        // Validate telecom-specific SLA terms
        $slaTerms = $this->getParsedSlaTerms();
        if (!empty($slaTerms)) {
            // Voice quality requirements
            if (empty($slaTerms['voice_quality_target'])) {
                $validator->errors()->add('sla_terms', 'Voice quality targets are required for telecom services.');
            }
            
            // Network availability requirements
            if (empty($slaTerms['uptimePercentage']) || (float)$slaTerms['uptimePercentage'] < 99.0) {
                $validator->errors()->add('sla_terms', 'Telecom services require minimum 99.0% uptime commitment.');
            }
        }
    }

    /**
     * Validate hardware template requirements
     */
    protected function validateHardwareTemplate($validator): void
    {
        $pricingSchedule = $this->getParsedPricingSchedule();
        
        // Hardware templates should have hardware pricing
        if (empty($pricingSchedule['hardwarePricing'])) {
            $validator->errors()->add('pricing_schedule', 'Hardware pricing configuration is required for hardware contracts.');
        }
        
        // Validate installation and project management rates
        if (!empty($pricingSchedule['hardwarePricing'])) {
            $hardwarePricing = $pricingSchedule['hardwarePricing'];
            
            if (empty($hardwarePricing['installationRate'])) {
                $validator->errors()->add('pricing_schedule', 'Installation rate is required for hardware services.');
            }
            
            if (empty($hardwarePricing['projectManagementRate'])) {
                $validator->errors()->add('pricing_schedule', 'Project management rate is required for hardware installations.');
            }
        }
    }

    /**
     * Validate compliance template requirements
     */
    protected function validateComplianceTemplate($validator): void
    {
        $pricingSchedule = $this->getParsedPricingSchedule();
        
        // Compliance templates should have compliance pricing
        if (empty($pricingSchedule['compliancePricing'])) {
            $validator->errors()->add('pricing_schedule', 'Compliance framework pricing is required for compliance contracts.');
        }
        
        // Validate that at least one compliance framework is selected
        if (!empty($pricingSchedule['compliancePricing']['frameworkMonthly'])) {
            $hasActiveFramework = false;
            foreach ($pricingSchedule['compliancePricing']['frameworkMonthly'] as $framework => $fee) {
                if (!empty($fee) && (float)$fee > 0) {
                    $hasActiveFramework = true;
                    break;
                }
            }
            
            if (!$hasActiveFramework) {
                $validator->errors()->add('pricing_schedule', 'At least one compliance framework must be configured with pricing.');
            }
        }
    }

    /**
     * Validate infrastructure template requirements
     */
    protected function validateInfrastructureTemplate($validator): void
    {
        $slaTerms = $this->getParsedSlaTerms();
        $infrastructureSchedule = $this->getParsedInfrastructureSchedule();
        
        // Check for supported asset types in multiple locations
        $hasAssetTypes = !empty($slaTerms['supported_asset_types']) || 
                        !empty($slaTerms['supportedAssetTypes']) ||
                        !empty($infrastructureSchedule['supportedAssetTypes']) ||
                        !empty($infrastructureSchedule['supported_asset_types']);
        
        if (!$hasAssetTypes) {
            $validator->errors()->add('infrastructure_schedule', 'Supported asset types must be specified for infrastructure contracts.');
        }
        
        // Check for response time requirements in multiple locations
        $hasResponseTimes = !empty($slaTerms['response_time_hours']) || 
                           !empty($slaTerms['responseTimeHours']) ||
                           (!empty($infrastructureSchedule['sla']) && !empty($infrastructureSchedule['sla']['responseTimeHours'])) ||
                           (!empty($slaTerms['slaCommitments']) && !empty($slaTerms['slaCommitments']['responseTime']));
        
        if (!$hasResponseTimes) {
            $validator->errors()->add('infrastructure_schedule', 'Response time commitments are required for infrastructure services.');
        }
    }

    /**
     * Get parsed pricing schedule from JSON
     */
    protected function getParsedPricingSchedule(): array
    {
        $pricingSchedule = $this->input('pricing_schedule');
        
        if (is_string($pricingSchedule)) {
            return json_decode($pricingSchedule, true) ?? [];
        }
        
        return is_array($pricingSchedule) ? $pricingSchedule : [];
    }

    /**
     * Get parsed SLA terms from JSON
     */
    protected function getParsedSlaTerms(): array
    {
        $slaTerms = $this->input('sla_terms');
        
        if (is_string($slaTerms)) {
            return json_decode($slaTerms, true) ?? [];
        }
        
        return is_array($slaTerms) ? $slaTerms : [];
    }

    /**
     * Get parsed infrastructure schedule from JSON
     */
    protected function getParsedInfrastructureSchedule(): array
    {
        $infrastructureSchedule = $this->input('infrastructure_schedule');
        
        if (is_string($infrastructureSchedule)) {
            return json_decode($infrastructureSchedule, true) ?? [];
        }
        
        return is_array($infrastructureSchedule) ? $infrastructureSchedule : [];
    }

    /**
     * Get validated data with computed fields.
     */
    public function validatedWithComputed(): array
    {
        $validated = $this->validated();

        // Calculate end_date if term_months provided
        if (!empty($validated['term_months']) && empty($validated['end_date'])) {
            $validated['end_date'] = now()->parse($validated['start_date'])
                ->addMonths((int) $validated['term_months'])
                ->format('Y-m-d');
        }

        // Process JSON schedule data
        if (!empty($validated['variable_values'])) {
            $validated['variable_values'] = json_decode($validated['variable_values'], true);
        }
        if (!empty($validated['billing_config'])) {
            $validated['billing_config'] = json_decode($validated['billing_config'], true);
        }
        if (!empty($validated['infrastructure_schedule'])) {
            $validated['infrastructure_schedule'] = json_decode($validated['infrastructure_schedule'], true);
        }
        if (!empty($validated['pricing_schedule'])) {
            $validated['pricing_schedule'] = json_decode($validated['pricing_schedule'], true);
        }
        if (!empty($validated['additional_terms'])) {
            $validated['additional_terms'] = json_decode($validated['additional_terms'], true);
        }

        // Calculate contract_value from pricing_schedule
        $validated['contract_value'] = $this->calculateContractValue($validated);

        // Set defaults
        $validated['status'] = $validated['status'] ?? Contract::STATUS_DRAFT;
        $validated['signature_status'] = $validated['signature_status'] ?? Contract::SIGNATURE_PENDING;
        $validated['currency_code'] = $validated['currency_code'] ?? 'USD';
        $validated['renewal_type'] = $validated['renewal_type'] ?? 'manual';
        $validated['auto_renewal'] = $validated['auto_renewal'] ?? false;

        return $validated;
    }

    /**
     * Calculate contract value from pricing schedule with edge case handling
     */
    protected function calculateContractValue(array $data): float
    {
        try {
            $totalValue = 0;
            $pricingSchedule = $data['pricing_schedule'] ?? [];
            $warnings = [];
            
            // Handle empty pricing schedule
            if (empty($pricingSchedule)) {
                \Log::warning('Contract pricing schedule is empty', [
                    'contract_data' => array_keys($data)
                ]);
                return 0;
            }

            // Base pricing with validation
            if (isset($pricingSchedule['basePricing'])) {
                $basePricing = $pricingSchedule['basePricing'];
                
                $monthlyBase = $this->safeFloatConversion($basePricing['monthlyBase'] ?? 0);
                $setupFee = $this->safeFloatConversion($basePricing['setupFee'] ?? 0);
                
                // Validate reasonable ranges
                if ($monthlyBase > 100000) {
                    $warnings[] = "Monthly base fee seems high: $" . number_format($monthlyBase, 2);
                }
                if ($setupFee > 50000) {
                    $warnings[] = "Setup fee seems high: $" . number_format($setupFee, 2);
                }
                
                $totalValue += $monthlyBase + $setupFee;
            }

            // Per-asset pricing with conflict detection
            if (isset($pricingSchedule['assetTypePricing'])) {
                $assetPricingTotal = 0;
                $configuredTypes = [];
                
                foreach ($pricingSchedule['assetTypePricing'] as $assetType => $config) {
                    if (!empty($config['enabled']) && isset($config['price'])) {
                        $price = $this->safeFloatConversion($config['price']);
                        
                        // Validate asset pricing
                        if ($price < 0) {
                            $warnings[] = "Negative pricing for {$assetType}: $" . number_format($price, 2);
                            continue;
                        }
                        
                        if ($price > 10000) {
                            $warnings[] = "High per-asset pricing for {$assetType}: $" . number_format($price, 2);
                        }
                        
                        $assetPricingTotal += $price;
                        $configuredTypes[] = $assetType;
                    }
                }
                
                // Check for pricing conflicts
                if (count($configuredTypes) > 10) {
                    $warnings[] = "Large number of asset types configured (" . count($configuredTypes) . ")";
                }
                
                $totalValue += $assetPricingTotal;
            }

            // Template-specific pricing with type validation
            if (isset($pricingSchedule['telecomPricing'])) {
                $telecomTotal = 0;
                $telecomPricing = $pricingSchedule['telecomPricing'];
                
                foreach ($telecomPricing as $key => $price) {
                    $safePrice = $this->safeFloatConversion($price);
                    
                    if ($safePrice < 0) {
                        $warnings[] = "Negative telecom pricing for {$key}: $" . number_format($safePrice, 2);
                        continue;
                    }
                    
                    $telecomTotal += $safePrice;
                }
                
                $totalValue += $telecomTotal;
            }

            if (isset($pricingSchedule['hardwarePricing'])) {
                $hardwarePricing = $pricingSchedule['hardwarePricing'];
                $installationRate = $this->safeFloatConversion($hardwarePricing['installationRate'] ?? 0);
                $projectRate = $this->safeFloatConversion($hardwarePricing['projectManagementRate'] ?? 0);
                
                // Validate hardware rates
                if ($installationRate > 500) {
                    $warnings[] = "High installation rate: $" . number_format($installationRate, 2);
                }
                if ($projectRate > 300) {
                    $warnings[] = "High project management rate: $" . number_format($projectRate, 2);
                }
                
                $totalValue += $installationRate + $projectRate;
            }

            if (isset($pricingSchedule['compliancePricing'])) {
                $complianceTotal = 0;
                
                foreach ($pricingSchedule['compliancePricing'] as $key => $price) {
                    if (is_array($price)) {
                        foreach ($price as $subKey => $subPrice) {
                            $safePr = $this->safeFloatConversion($subPrice);
                            if ($safePr > 0) {
                                $complianceTotal += $safePr;
                            }
                        }
                    } else {
                        $safePrice = $this->safeFloatConversion($price);
                        if ($safePrice > 0) {
                            $complianceTotal += $safePrice;
                        }
                    }
                }
                
                $totalValue += $complianceTotal;
            }

            // Per-user pricing with user count estimation
            if (isset($pricingSchedule['perUnitPricing']['perUser'])) {
                $perUserRate = $this->safeFloatConversion($pricingSchedule['perUnitPricing']['perUser']);
                
                if ($perUserRate > 0) {
                    // For initial calculation, assume 1 user (will be updated after contract creation)
                    $totalValue += $perUserRate;
                    
                    if ($perUserRate > 500) {
                        $warnings[] = "High per-user rate: $" . number_format($perUserRate, 2);
                    }
                }
            }

            // Final validation
            if ($totalValue < 0) {
                \Log::error('Calculated negative contract value', [
                    'calculated_value' => $totalValue,
                    'pricing_schedule' => $pricingSchedule
                ]);
                $totalValue = 0;
            }

            if ($totalValue > 1000000) {
                $warnings[] = "Very high total contract value: $" . number_format($totalValue, 2);
            }

            // Log warnings if any
            if (!empty($warnings)) {
                \Log::warning('Contract pricing calculation warnings', [
                    'warnings' => $warnings,
                    'total_value' => $totalValue
                ]);
            }

            return round($totalValue, 2);
            
        } catch (\Exception $e) {
            \Log::error('Error calculating contract value', [
                'error' => $e->getMessage(),
                'pricing_data' => $pricingSchedule ?? null
            ]);
            
            // Return 0 on calculation error to prevent contract creation failure
            return 0;
        }
    }

    /**
     * Safely convert value to float, handling various input types
     */
    protected function safeFloatConversion($value): float
    {
        // Handle null, empty string, or false
        if ($value === null || $value === '' || $value === false) {
            return 0;
        }

        // Handle arrays (shouldn't happen but be defensive)
        if (is_array($value)) {
            return 0;
        }

        // Handle string numbers with currency symbols or commas
        if (is_string($value)) {
            // Remove currency symbols and commas
            $cleaned = preg_replace('/[^0-9.-]/', '', $value);
            
            // Handle empty string after cleaning
            if ($cleaned === '' || $cleaned === '-') {
                return 0;
            }
            
            $value = $cleaned;
        }

        // Convert to float
        $floatValue = (float) $value;

        // Handle infinite or NaN values
        if (!is_finite($floatValue)) {
            return 0;
        }

        return $floatValue;
    }
}