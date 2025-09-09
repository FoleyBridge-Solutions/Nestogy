<?php

namespace App\Domains\Contract\Requests;

use App\Domains\Contract\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $contract = $this->route('contract');
        return $this->user()->can('update', $contract);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $contract = $this->route('contract');

        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string|max:2000',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|nullable|date|after:start_date',
            'term_months' => 'sometimes|nullable|integer|min:1|max:120',
            'renewal_type' => [
                'sometimes',
                'string',
                Rule::in([
                    Contract::RENEWAL_NONE,
                    Contract::RENEWAL_MANUAL,
                    Contract::RENEWAL_AUTOMATIC,
                    Contract::RENEWAL_NEGOTIATED,
                ])
            ],
            'renewal_notice_days' => 'sometimes|nullable|integer|min:1|max:365',
            'auto_renewal' => 'sometimes|boolean',
            'contract_value' => 'sometimes|numeric|min:0|max:999999999.99',
            'currency_code' => 'sometimes|string|size:3|in:USD,EUR,GBP,CAD,AUD,JPY',
            'payment_terms' => 'sometimes|nullable|string|max:1000',
            'pricing_structure' => 'sometimes|nullable|array',
            'pricing_structure.recurring_monthly' => 'nullable|numeric|min:0',
            'pricing_structure.one_time' => 'nullable|numeric|min:0',
            'pricing_structure.setup_fee' => 'nullable|numeric|min:0',
            'pricing_structure.renewal_adjustment' => 'nullable|array',
            'sla_terms' => 'sometimes|nullable|array',
            'sla_terms.response_time_hours' => 'nullable|numeric|min:0.1|max:72',
            'sla_terms.resolution_time_hours' => 'nullable|numeric|min:0.1|max:720',
            'sla_terms.uptime_percentage' => 'nullable|numeric|min:90|max:100',
            'voip_specifications' => 'sometimes|nullable|array',
            'voip_specifications.services' => 'nullable|array',
            'voip_specifications.equipment' => 'nullable|array',
            'voip_specifications.phone_numbers' => 'nullable|integer|min:0',
            'compliance_requirements' => 'sometimes|nullable|array',
            'terms_and_conditions' => 'sometimes|nullable|string',
            'custom_clauses' => 'sometimes|nullable|array',
            'termination_clause' => 'sometimes|nullable|string',
            'liability_clause' => 'sometimes|nullable|string',
            'confidentiality_clause' => 'sometimes|nullable|string',
            'dispute_resolution' => 'sometimes|nullable|string|max:1000',
            'governing_law' => 'sometimes|nullable|string|max:255',
            'jurisdiction' => 'sometimes|nullable|string|max:255',
            'milestones' => 'sometimes|nullable|array',
            'milestones.*.title' => 'required_with:milestones|string|max:255',
            'milestones.*.description' => 'nullable|string|max:1000',
            'milestones.*.due_date' => 'required_with:milestones|date',
            'milestones.*.value' => 'nullable|numeric|min:0',
            'deliverables' => 'sometimes|nullable|array',
            'deliverables.*.title' => 'required_with:deliverables|string|max:255',
            'deliverables.*.description' => 'nullable|string|max:1000',
            'deliverables.*.due_date' => 'required_with:deliverables|date',
            'penalties' => 'sometimes|nullable|array',
            'penalties.*.type' => 'required_with:penalties|string|max:100',
            'penalties.*.amount' => 'required_with:penalties|numeric|min:0',
            'penalties.*.description' => 'nullable|string|max:500',
            'metadata' => 'sometimes|nullable|array',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'end_date.after' => 'The end date must be after the start date.',
            'term_months.max' => 'The term cannot exceed 120 months (10 years).',
            'renewal_notice_days.max' => 'The renewal notice period cannot exceed 365 days.',
            'contract_value.max' => 'The contract value cannot exceed $999,999,999.99.',
            'currency_code.size' => 'The currency code must be exactly 3 characters.',
            'currency_code.in' => 'The currency code must be one of: USD, EUR, GBP, CAD, AUD, JPY.',
            'sla_terms.response_time_hours.max' => 'Response time cannot exceed 72 hours.',
            'sla_terms.resolution_time_hours.max' => 'Resolution time cannot exceed 720 hours (30 days).',
            'sla_terms.uptime_percentage.min' => 'Uptime percentage must be at least 90%.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $contract = $this->route('contract');

            // Only allow editing of draft and pending review contracts
            if (!in_array($contract->status, [Contract::STATUS_DRAFT, Contract::STATUS_PENDING_REVIEW])) {
                $validator->errors()->add('status', 'Only draft and pending review contracts can be edited.');
                return;
            }

            // Validate that end_date is required if term_months is not provided
            $endDate = $this->input('end_date', $contract->end_date);
            $termMonths = $this->input('term_months', $contract->term_months);
            
            if (!$endDate && !$termMonths) {
                $validator->errors()->add('end_date', 'Either end date or term in months must be provided.');
                $validator->errors()->add('term_months', 'Either end date or term in months must be provided.');
            }

            // Validate that auto_renewal requires renewal_notice_days
            $autoRenewal = $this->input('auto_renewal', $contract->auto_renewal);
            $renewalNoticeDays = $this->input('renewal_notice_days', $contract->renewal_notice_days);
            
            if ($autoRenewal && !$renewalNoticeDays) {
                $validator->errors()->add('renewal_notice_days', 'Renewal notice days is required when auto renewal is enabled.');
            }

            // Validate pricing structure
            if ($this->has('pricing_structure') && $this->pricing_structure) {
                $contractValue = $this->input('contract_value', $contract->contract_value);
                $total = ($this->input('pricing_structure.recurring_monthly', 0) * 12) + 
                        $this->input('pricing_structure.one_time', 0) +
                        $this->input('pricing_structure.setup_fee', 0);
                
                if ($total > $contractValue) {
                    $validator->errors()->add('pricing_structure', 'Total pricing structure cannot exceed contract value.');
                }
            }

            // Validate start date changes don't affect signed/active contracts
            if ($this->has('start_date') && $this->start_date !== $contract->start_date->format('Y-m-d')) {
                if (in_array($contract->status, [Contract::STATUS_SIGNED, Contract::STATUS_ACTIVE])) {
                    $validator->errors()->add('start_date', 'Cannot change start date of signed or active contracts.');
                }
            }

            // Validate that milestones due dates are after start date
            if ($this->has('milestones') && $this->milestones) {
                $startDate = $this->input('start_date', $contract->start_date->format('Y-m-d'));
                foreach ($this->milestones as $index => $milestone) {
                    if (isset($milestone['due_date']) && $milestone['due_date'] <= $startDate) {
                        $validator->errors()->add("milestones.{$index}.due_date", 'Milestone due date must be after the contract start date.');
                    }
                }
            }
        });
    }

    /**
     * Get only the fields that can be updated based on contract status.
     */
    public function getUpdatableFields(): array
    {
        $contract = $this->route('contract');
        $validated = $this->validated();

        // Remove fields that cannot be updated for certain statuses
        if ($contract->status === Contract::STATUS_PENDING_REVIEW) {
            // More restrictive for pending review
            $allowedFields = [
                'title', 'description', 'payment_terms', 'terms_and_conditions',
                'custom_clauses', 'metadata'
            ];
            
            return array_intersect_key($validated, array_flip($allowedFields));
        }

        return $validated;
    }
}