<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessUsageDataRequest extends FormRequest
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
            'data_source' => 'required|in:cdr_records,usage_file,manual_usage,api_import',
            'processing_date' => 'nullable|date',
            'overwrite_existing' => 'nullable|boolean',
            'validate_only' => 'nullable|boolean',
            
            // CDR Records
            'cdr_records' => 'required_if:data_source,cdr_records|array',
            'cdr_records.*.id' => 'nullable|string',
            'cdr_records.*.call_date' => 'required|date',
            'cdr_records.*.from_number' => 'required|string|max:20',
            'cdr_records.*.to_number' => 'required|string|max:20',
            'cdr_records.*.duration' => 'required|integer|min:0',
            'cdr_records.*.service_type' => 'nullable|string|max:100',
            'cdr_records.*.call_type' => 'nullable|string|max:50',
            'cdr_records.*.rate' => 'nullable|numeric|min:0',
            
            // Usage File
            'usage_file' => 'required_if:data_source,usage_file|file|mimes:csv,txt,json,xml|max:10240',
            'file_format' => 'required_if:data_source,usage_file|in:csv,json,xml,custom',
            'file_mapping' => 'nullable|array',
            'file_mapping.date_column' => 'nullable|string',
            'file_mapping.usage_column' => 'nullable|string',
            'file_mapping.service_column' => 'nullable|string',
            'file_mapping.rate_column' => 'nullable|string',
            'skip_header_rows' => 'nullable|integer|min:0|max:10',
            
            // Manual Usage
            'manual_usage' => 'required_if:data_source,manual_usage|array',
            'manual_usage.*.service_type' => 'required|string|max:100',
            'manual_usage.*.usage_date' => 'required|date',
            'manual_usage.*.usage_amount' => 'required|numeric|min:0',
            'manual_usage.*.usage_unit' => 'nullable|string|max:20',
            'manual_usage.*.rate' => 'nullable|numeric|min:0',
            'manual_usage.*.cost' => 'nullable|numeric|min:0',
            'manual_usage.*.description' => 'nullable|string|max:255',
            
            // API Import
            'api_endpoint' => 'required_if:data_source,api_import|url',
            'api_credentials' => 'nullable|array',
            'api_credentials.username' => 'nullable|string',
            'api_credentials.password' => 'nullable|string',
            'api_credentials.api_key' => 'nullable|string',
            'api_credentials.token' => 'nullable|string',
            'date_range' => 'nullable|array',
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date|after_or_equal:date_range.start',
            
            // Processing Options
            'processing_options' => 'nullable|array',
            'processing_options.apply_rates' => 'nullable|boolean',
            'processing_options.calculate_costs' => 'nullable|boolean',
            'processing_options.group_by_service' => 'nullable|boolean',
            'processing_options.deduplicate' => 'nullable|boolean',
            'processing_options.validate_numbers' => 'nullable|boolean',
            'processing_options.minimum_duration' => 'nullable|integer|min:0',
            'processing_options.round_duration' => 'nullable|in:none,up,down,nearest',
            
            // Filtering Options
            'filters' => 'nullable|array',
            'filters.date_from' => 'nullable|date',
            'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
            'filters.service_types' => 'nullable|array',
            'filters.service_types.*' => 'string|max:100',
            'filters.min_duration' => 'nullable|integer|min:0',
            'filters.max_duration' => 'nullable|integer|min:0',
            'filters.number_patterns' => 'nullable|array',
            'filters.number_patterns.*' => 'string|max:50',
            
            // Notification settings
            'notifications' => 'nullable|array',
            'notifications.send_completion_email' => 'nullable|boolean',
            'notifications.email_recipients' => 'nullable|array',
            'notifications.email_recipients.*' => 'email',
            'notifications.webhook_url' => 'nullable|url',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'data_source.required' => 'Data source is required.',
            'data_source.in' => 'Invalid data source. Must be cdr_records, usage_file, manual_usage, or api_import.',
            'processing_date.date' => 'Processing date must be a valid date.',
            'overwrite_existing.boolean' => 'Overwrite existing must be true or false.',
            'validate_only.boolean' => 'Validate only must be true or false.',
            
            // CDR Records messages
            'cdr_records.required_if' => 'CDR records are required when data source is cdr_records.',
            'cdr_records.array' => 'CDR records must be an array.',
            'cdr_records.*.call_date.required' => 'Call date is required for each CDR record.',
            'cdr_records.*.call_date.date' => 'Call date must be a valid date.',
            'cdr_records.*.from_number.required' => 'From number is required for each CDR record.',
            'cdr_records.*.from_number.max' => 'From number cannot exceed 20 characters.',
            'cdr_records.*.to_number.required' => 'To number is required for each CDR record.',
            'cdr_records.*.to_number.max' => 'To number cannot exceed 20 characters.',
            'cdr_records.*.duration.required' => 'Duration is required for each CDR record.',
            'cdr_records.*.duration.integer' => 'Duration must be an integer.',
            'cdr_records.*.duration.min' => 'Duration must be 0 or greater.',
            
            // Usage File messages
            'usage_file.required_if' => 'Usage file is required when data source is usage_file.',
            'usage_file.file' => 'Usage file must be a valid file.',
            'usage_file.mimes' => 'Usage file must be a CSV, TXT, JSON, or XML file.',
            'usage_file.max' => 'Usage file cannot exceed 10MB.',
            'file_format.required_if' => 'File format is required when data source is usage_file.',
            'file_format.in' => 'Invalid file format. Must be csv, json, xml, or custom.',
            'skip_header_rows.integer' => 'Skip header rows must be an integer.',
            'skip_header_rows.max' => 'Cannot skip more than 10 header rows.',
            
            // Manual Usage messages
            'manual_usage.required_if' => 'Manual usage data is required when data source is manual_usage.',
            'manual_usage.array' => 'Manual usage must be an array.',
            'manual_usage.*.service_type.required' => 'Service type is required for each manual usage entry.',
            'manual_usage.*.usage_date.required' => 'Usage date is required for each manual usage entry.',
            'manual_usage.*.usage_date.date' => 'Usage date must be a valid date.',
            'manual_usage.*.usage_amount.required' => 'Usage amount is required for each manual usage entry.',
            'manual_usage.*.usage_amount.numeric' => 'Usage amount must be a number.',
            'manual_usage.*.usage_amount.min' => 'Usage amount must be 0 or greater.',
            
            // API Import messages
            'api_endpoint.required_if' => 'API endpoint is required when data source is api_import.',
            'api_endpoint.url' => 'API endpoint must be a valid URL.',
            'date_range.end.after_or_equal' => 'End date must be on or after the start date.',
            
            // Processing Options messages
            'processing_options.minimum_duration.integer' => 'Minimum duration must be an integer.',
            'processing_options.round_duration.in' => 'Round duration must be none, up, down, or nearest.',
            
            // Filtering messages
            'filters.date_to.after_or_equal' => 'Filter end date must be on or after the start date.',
            'filters.service_types.array' => 'Service types filter must be an array.',
            'filters.min_duration.integer' => 'Minimum duration filter must be an integer.',
            'filters.max_duration.integer' => 'Maximum duration filter must be an integer.',
            
            // Notification messages
            'notifications.email_recipients.*.email' => 'Each email recipient must be a valid email address.',
            'notifications.webhook_url.url' => 'Webhook URL must be a valid URL.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'data_source' => 'data source',
            'processing_date' => 'processing date',
            'overwrite_existing' => 'overwrite existing data',
            'validate_only' => 'validation only mode',
            'usage_file' => 'usage file',
            'file_format' => 'file format',
            'skip_header_rows' => 'header rows to skip',
            'api_endpoint' => 'API endpoint',
            'date_range.start' => 'start date',
            'date_range.end' => 'end date',
            'filters.date_from' => 'filter start date',
            'filters.date_to' => 'filter end date',
            'notifications.webhook_url' => 'webhook URL',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans
        $this->merge([
            'overwrite_existing' => $this->boolean('overwrite_existing', false),
            'validate_only' => $this->boolean('validate_only', false),
        ]);

        // Set default processing date if not provided
        if (!$this->has('processing_date') || empty($this->input('processing_date'))) {
            $this->merge(['processing_date' => now()->toDateString()]);
        }

        // Parse JSON strings for complex fields if they come as strings
        if (is_string($this->cdr_records)) {
            $this->merge(['cdr_records' => json_decode($this->cdr_records, true)]);
        }
        if (is_string($this->manual_usage)) {
            $this->merge(['manual_usage' => json_decode($this->manual_usage, true)]);
        }
        if (is_string($this->file_mapping)) {
            $this->merge(['file_mapping' => json_decode($this->file_mapping, true)]);
        }
        if (is_string($this->api_credentials)) {
            $this->merge(['api_credentials' => json_decode($this->api_credentials, true)]);
        }
        if (is_string($this->processing_options)) {
            $this->merge(['processing_options' => json_decode($this->processing_options, true)]);
        }
        if (is_string($this->filters)) {
            $this->merge(['filters' => json_decode($this->filters, true)]);
        }
        if (is_string($this->notifications)) {
            $this->merge(['notifications' => json_decode($this->notifications, true)]);
        }

        // Set default skip header rows for CSV files
        if ($this->input('file_format') === 'csv' && !$this->has('skip_header_rows')) {
            $this->merge(['skip_header_rows' => 1]);
        }
    }

    /**
     * Get the validated data with defaults applied.
     */
    public function validatedWithDefaults(): array
    {
        $validated = $this->validated();

        return array_merge([
            'processing_date' => now()->toDateString(),
            'overwrite_existing' => false,
            'validate_only' => false,
            'processing_options' => [
                'apply_rates' => true,
                'calculate_costs' => true,
                'group_by_service' => true,
                'deduplicate' => true,
                'validate_numbers' => true,
                'round_duration' => 'up',
            ],
            'notifications' => [
                'send_completion_email' => false,
            ],
        ], $validated);
    }
}