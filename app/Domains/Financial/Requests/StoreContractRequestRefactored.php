<?php

namespace App\Domains\Financial\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\Contract;
use Illuminate\Validation\Rule;

class StoreContractRequestRefactored extends BaseFormRequest
{
    protected function initializeRequest(): void
    {
        $this->modelClass = Contract::class;
        $this->requiresCompanyValidation = true;
    }

    protected function getSpecificRules(): array
    {
        return [
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
            'title' => 'required|string|max:255',
            'value' => $this->getNumericAmountRule(),
            'currency' => $this->getCurrencyValidationRule(),
            'start_date' => $this->getDateRule(),
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'renewal_type' => [
                'nullable',
                'string',
                Rule::in(['manual', 'automatic', 'notification'])
            ],
            'renewal_period' => 'nullable|integer|min:1|max:60',
            'renewal_unit' => [
                'nullable',
                'string',
                Rule::in(['days', 'weeks', 'months', 'years'])
            ],
            'payment_terms' => 'nullable|integer|min:0|max:365',
            'late_fee_rate' => 'nullable|numeric|min:0|max:100',
            'auto_renew' => 'boolean',
            'send_reminders' => 'boolean',
            'reminder_days' => 'nullable|integer|min:1|max:365',
            'terms_and_conditions' => 'nullable|string|max:10000',
            'special_clauses' => 'nullable|string|max:5000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,txt',
        ];
    }

    protected function getExpectedFields(): array
    {
        return [
            'name', 'description', 'notes', 'status', 'client_id'
        ];
    }

    protected function getSpecificMessages(): array
    {
        return [
            'contract_type.in' => 'The selected contract type is invalid.',
            'end_date.after' => 'The end date must be after the start date.',
            'value.numeric' => 'The contract value must be a valid number.',
            'payment_terms.max' => 'Payment terms cannot exceed 365 days.',
            'late_fee_rate.max' => 'Late fee rate cannot exceed 100%.',
            'client_id.exists' => 'The selected client does not exist or does not belong to your company.',
            'quote_id.exists' => 'The selected quote does not exist or does not belong to your company.',
            'template_id.exists' => 'The selected template does not exist or does not belong to your company.',
        ];
    }

    protected function getSpecificAttributes(): array
    {
        return [
            'client_id' => 'client',
            'quote_id' => 'quote',
            'template_id' => 'template',
            'contract_type' => 'contract type',
            'start_date' => 'start date',
            'end_date' => 'end date',
            'renewal_type' => 'renewal type',
            'renewal_period' => 'renewal period',
            'renewal_unit' => 'renewal unit',
            'payment_terms' => 'payment terms',
            'late_fee_rate' => 'late fee rate',
            'auto_renew' => 'auto renewal',
            'send_reminders' => 'send reminders',
            'reminder_days' => 'reminder days',
            'terms_and_conditions' => 'terms and conditions',
            'special_clauses' => 'special clauses',
        ];
    }

    protected function prepareSpecificFields(): void
    {
        // Set default status if not provided
        if (!$this->filled('status')) {
            $this->merge(['status' => Contract::STATUS_DRAFT]);
        }

        // Set default currency if not provided
        if (!$this->filled('currency')) {
            $this->merge(['currency' => 'USD']);
        }

        // Set default payment terms if not provided
        if (!$this->filled('payment_terms')) {
            $this->merge(['payment_terms' => 30]);
        }

        // Set default renewal settings
        if ($this->boolean('auto_renew') && !$this->filled('renewal_period')) {
            $this->merge([
                'renewal_period' => 12,
                'renewal_unit' => 'months'
            ]);
        }

        // Set default reminder settings
        if ($this->boolean('send_reminders') && !$this->filled('reminder_days')) {
            $this->merge(['reminder_days' => 30]);
        }
    }

    protected function performSpecificValidation($validator): void
    {
        // Validate that renewal settings are complete if auto_renew is enabled
        if ($this->boolean('auto_renew')) {
            if (!$this->filled('renewal_period') || !$this->filled('renewal_unit')) {
                $validator->errors()->add('renewal_period', 'Renewal period and unit are required when auto-renewal is enabled.');
            }
        }

        // Validate that reminder settings are complete if send_reminders is enabled
        if ($this->boolean('send_reminders')) {
            if (!$this->filled('reminder_days')) {
                $validator->errors()->add('reminder_days', 'Reminder days are required when reminders are enabled.');
            }
        }

        // Validate end date is provided for non-perpetual contracts
        if ($this->filled('contract_type') && $this->contract_type !== 'perpetual') {
            if (!$this->filled('end_date')) {
                $validator->errors()->add('end_date', 'End date is required for time-limited contracts.');
            }
        }

        // Validate that quote belongs to the selected client
        if ($this->filled('quote_id') && $this->filled('client_id')) {
            $quote = \App\Models\Quote::find($this->quote_id);
            if ($quote && $quote->client_id !== (int) $this->client_id) {
                $validator->errors()->add('quote_id', 'The selected quote does not belong to the selected client.');
            }
        }
    }

    protected function getUniqueFields(): array
    {
        return ['title']; // Contract titles should be unique within a company
    }

    protected function getBooleanFields(): array
    {
        return array_merge(parent::getBooleanFields(), [
            'auto_renew', 
            'send_reminders'
        ]);
    }
}