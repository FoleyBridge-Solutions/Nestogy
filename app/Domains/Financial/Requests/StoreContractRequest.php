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
                Rule::in(array_keys(Contract::getAvailableTypes()))
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
                'required',
                'string',
                Rule::in([
                    Contract::RENEWAL_NONE,
                    Contract::RENEWAL_MANUAL,
                    Contract::RENEWAL_AUTOMATIC,
                    Contract::RENEWAL_NEGOTIATED,
                ])
            ],
            'renewal_notice_days' => 'nullable|integer|min:1|max:365',
            'auto_renewal' => 'boolean',
            'contract_value' => 'nullable|numeric|min:0|max:999999999.99',
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
            'contract_value.max' => 'The contract value cannot exceed $999,999,999.99.',
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
            // Validate contract value based on billing model
            $template = null;
            if ($this->template_id) {
                $template = \App\Models\Financial\ContractTemplate::find($this->template_id);
            }
            
            // Require contract_value for fixed billing or no template
            $requiresValue = !$template || $template->billing_model === 'fixed';
            if ($requiresValue && !$this->contract_value) {
                $validator->errors()->add('contract_value', 'Contract value is required for fixed price contracts.');
            }
            
            // For programmable contracts, set default value if not provided
            if (!$requiresValue && !$this->contract_value) {
                $this->merge(['contract_value' => 0]);
            }
            
            // Validate that end_date is required if term_months is not provided
            if (!$this->end_date && !$this->term_months) {
                $validator->errors()->add('end_date', 'Either end date or term in months must be provided.');
                $validator->errors()->add('term_months', 'Either end date or term in months must be provided.');
            }

            // Validate that auto_renewal requires renewal_notice_days
            if ($this->auto_renewal && !$this->renewal_notice_days) {
                $validator->errors()->add('renewal_notice_days', 'Renewal notice days is required when auto renewal is enabled.');
            }

            // Validate pricing structure (only for fixed billing contracts)
            if ($this->pricing_structure && $requiresValue && $this->contract_value > 0) {
                $total = ($this->input('pricing_structure.recurring_monthly', 0) * 12) + 
                        $this->input('pricing_structure.one_time', 0) +
                        $this->input('pricing_structure.setup_fee', 0);
                
                if ($total > $this->contract_value) {
                    $validator->errors()->add('pricing_structure', 'Total pricing structure cannot exceed contract value.');
                }
            }

            // Validate VoIP specifications for VoIP contract types
            $voipTypes = [
                Contract::TYPE_SERVICE_AGREEMENT,
                Contract::TYPE_SLA_CONTRACT,
                Contract::TYPE_INTERNATIONAL_SERVICE,
            ];

            if (in_array($this->contract_type, $voipTypes) && !$this->voip_specifications) {
                $validator->errors()->add('voip_specifications', 'VoIP specifications are required for this contract type.');
            }
        });
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
                ->addMonths($validated['term_months'])
                ->format('Y-m-d');
        }

        // Set defaults
        $validated['status'] = $validated['status'] ?? Contract::STATUS_DRAFT;
        $validated['signature_status'] = $validated['signature_status'] ?? Contract::SIGNATURE_PENDING;
        $validated['currency_code'] = $validated['currency_code'] ?? 'USD';
        $validated['renewal_type'] = $validated['renewal_type'] ?? Contract::RENEWAL_MANUAL;
        $validated['auto_renewal'] = $validated['auto_renewal'] ?? false;

        return $validated;
    }
}