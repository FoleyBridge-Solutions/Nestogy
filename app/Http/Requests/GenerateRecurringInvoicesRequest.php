<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateRecurringInvoicesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization will be handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'recurring_ids' => 'nullable|array',
            'recurring_ids.*' => 'integer|exists:recurring,id',
            'billing_date' => 'nullable|date',
            'dry_run' => 'nullable|boolean',
            'send_emails' => 'nullable|boolean',
            'include_usage_data' => 'nullable|boolean',
            'batch_size' => 'nullable|integer|min:1|max:1000',
            'filters' => 'nullable|array',
            'filters.client_id' => 'nullable|integer|exists:clients,id',
            'filters.billing_type' => 'nullable|in:flat,usage_based,tiered,hybrid,volume_discount',
            'filters.service_type' => 'nullable|string|max:100',
            'filters.status' => 'nullable|boolean',
            'filters.amount_min' => 'nullable|numeric|min:0',
            'filters.amount_max' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'recurring_ids.array' => 'Recurring IDs must be an array.',
            'recurring_ids.*.integer' => 'Each recurring ID must be an integer.',
            'recurring_ids.*.exists' => 'One or more recurring billing records do not exist.',
            'billing_date.date' => 'Billing date must be a valid date.',
            'dry_run.boolean' => 'Dry run must be true or false.',
            'send_emails.boolean' => 'Send emails must be true or false.',
            'include_usage_data.boolean' => 'Include usage data must be true or false.',
            'batch_size.integer' => 'Batch size must be an integer.',
            'batch_size.min' => 'Batch size must be at least 1.',
            'batch_size.max' => 'Batch size cannot exceed 1000.',
            'filters.array' => 'Filters must be an array.',
            'filters.client_id.integer' => 'Client ID filter must be an integer.',
            'filters.client_id.exists' => 'The specified client does not exist.',
            'filters.billing_type.in' => 'Invalid billing type filter.',
            'filters.service_type.string' => 'Service type filter must be a string.',
            'filters.service_type.max' => 'Service type filter cannot exceed 100 characters.',
            'filters.status.boolean' => 'Status filter must be true or false.',
            'filters.amount_min.numeric' => 'Minimum amount filter must be a number.',
            'filters.amount_min.min' => 'Minimum amount filter must be 0 or greater.',
            'filters.amount_max.numeric' => 'Maximum amount filter must be a number.',
            'filters.amount_max.min' => 'Maximum amount filter must be 0 or greater.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'recurring_ids' => 'recurring billing records',
            'billing_date' => 'billing date',
            'dry_run' => 'dry run mode',
            'send_emails' => 'email sending',
            'include_usage_data' => 'usage data inclusion',
            'batch_size' => 'batch size',
            'filters.client_id' => 'client filter',
            'filters.billing_type' => 'billing type filter',
            'filters.service_type' => 'service type filter',
            'filters.status' => 'status filter',
            'filters.amount_min' => 'minimum amount filter',
            'filters.amount_max' => 'maximum amount filter',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans
        $this->merge([
            'dry_run' => $this->boolean('dry_run', false),
            'send_emails' => $this->boolean('send_emails', true),
            'include_usage_data' => $this->boolean('include_usage_data', true),
        ]);

        // Set default billing date to today if not provided
        if (!$this->has('billing_date') || empty($this->input('billing_date'))) {
            $this->merge(['billing_date' => now()->toDateString()]);
        }

        // Set default batch size if not provided
        if (!$this->has('batch_size') || empty($this->input('batch_size'))) {
            $this->merge(['batch_size' => 100]);
        }

        // Parse JSON strings for complex fields if they come as strings
        if (is_string($this->recurring_ids)) {
            $this->merge(['recurring_ids' => json_decode($this->recurring_ids, true)]);
        }
        if (is_string($this->filters)) {
            $this->merge(['filters' => json_decode($this->filters, true)]);
        }
    }

    /**
     * Get the validated data with defaults applied.
     */
    public function validatedWithDefaults(): array
    {
        $validated = $this->validated();

        return array_merge([
            'dry_run' => false,
            'send_emails' => true,
            'include_usage_data' => true,
            'batch_size' => 100,
            'billing_date' => now()->toDateString(),
            'filters' => [],
        ], $validated);
    }
}