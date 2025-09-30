<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SLARequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('manage_slas');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $slaId = $this->route('sla')?->id;

        return [
            // Basic Information
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('slas')->where('company_id', auth()->user()->company_id)->ignore($slaId),
            ],
            'description' => 'nullable|string|max:1000',
            'is_default' => 'boolean',
            'is_active' => 'boolean',

            // Response Times (in minutes)
            'critical_response_minutes' => 'required|integer|min:5|max:1440',
            'high_response_minutes' => 'required|integer|min:15|max:2880',
            'medium_response_minutes' => 'required|integer|min:30|max:4320',
            'low_response_minutes' => 'required|integer|min:60|max:10080',

            // Resolution Times (in minutes)
            'critical_resolution_minutes' => 'required|integer|min:30|max:2880',
            'high_resolution_minutes' => 'required|integer|min:120|max:4320',
            'medium_resolution_minutes' => 'required|integer|min:480|max:10080',
            'low_resolution_minutes' => 'required|integer|min:1440|max:20160',

            // Business Hours & Coverage
            'business_hours_start' => 'required|date_format:H:i',
            'business_hours_end' => 'required|date_format:H:i|after:business_hours_start',
            'business_days' => 'required|array|min:1',
            'business_days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'timezone' => 'required|string|max:50',
            'coverage_type' => 'required|in:24/7,business_hours,custom',
            'holiday_coverage' => 'boolean',
            'exclude_weekends' => 'boolean',

            // Escalation Settings
            'escalation_enabled' => 'boolean',
            'escalation_levels' => 'nullable|array',
            'escalation_levels.*.percentage' => 'integer|min:25|max:100',
            'escalation_levels.*.action' => 'string|in:notify_assignee,notify_manager,notify_client,change_priority,reassign',
            'escalation_levels.*.notification_channels' => 'nullable|array',
            'escalation_levels.*.notification_channels.*' => 'string|in:email,sms,slack,teams,webhook',
            'breach_warning_percentage' => 'required|integer|min:50|max:95',

            // Performance Targets
            'uptime_percentage' => 'required|numeric|min:90|max:100',
            'first_call_resolution_target' => 'required|numeric|min:10|max:100',
            'customer_satisfaction_target' => 'required|numeric|min:50|max:100',

            // Notifications
            'notify_on_breach' => 'boolean',
            'notify_on_warning' => 'boolean',
            'notification_emails' => 'nullable|array',
            'notification_emails.*' => 'email',

            // Validity Period
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'An SLA with this name already exists for your company.',
            'business_hours_end.after' => 'Business hours end time must be after start time.',
            'business_days.min' => 'At least one business day must be selected.',
            'critical_response_minutes.min' => 'Critical response time must be at least 5 minutes.',
            'critical_response_minutes.max' => 'Critical response time cannot exceed 24 hours.',
            'critical_resolution_minutes.min' => 'Critical resolution time must be at least 30 minutes.',
            'high_response_minutes.min' => 'High priority response time must be at least 15 minutes.',
            'medium_response_minutes.min' => 'Medium priority response time must be at least 30 minutes.',
            'low_response_minutes.min' => 'Low priority response time must be at least 1 hour.',
            'breach_warning_percentage.min' => 'Warning percentage must be at least 50%.',
            'breach_warning_percentage.max' => 'Warning percentage cannot exceed 95%.',
            'uptime_percentage.min' => 'Uptime target must be at least 90%.',
            'first_call_resolution_target.min' => 'First call resolution target must be at least 10%.',
            'customer_satisfaction_target.min' => 'Customer satisfaction target must be at least 50%.',
            'effective_to.after' => 'Effective to date must be after effective from date.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'critical_response_minutes' => 'critical priority response time',
            'high_response_minutes' => 'high priority response time',
            'medium_response_minutes' => 'medium priority response time',
            'low_response_minutes' => 'low priority response time',
            'critical_resolution_minutes' => 'critical priority resolution time',
            'high_resolution_minutes' => 'high priority resolution time',
            'medium_resolution_minutes' => 'medium priority resolution time',
            'low_resolution_minutes' => 'low priority resolution time',
            'business_hours_start' => 'business hours start time',
            'business_hours_end' => 'business hours end time',
            'breach_warning_percentage' => 'breach warning threshold',
            'uptime_percentage' => 'uptime percentage target',
            'first_call_resolution_target' => 'first call resolution target',
            'customer_satisfaction_target' => 'customer satisfaction target',
            'effective_from' => 'effective from date',
            'effective_to' => 'effective to date',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate response times are less than resolution times
            $priorities = ['critical', 'high', 'medium', 'low'];

            foreach ($priorities as $priority) {
                $responseField = $priority.'_response_minutes';
                $resolutionField = $priority.'_resolution_minutes';

                if ($this->has($responseField) && $this->has($resolutionField)) {
                    if ($this->$responseField >= $this->$resolutionField) {
                        $validator->errors()->add(
                            $resolutionField,
                            "Resolution time must be greater than response time for {$priority} priority."
                        );
                    }
                }
            }

            // Validate escalation levels if provided
            if ($this->escalation_enabled && $this->escalation_levels) {
                $percentages = collect($this->escalation_levels)
                    ->pluck('percentage')
                    ->filter()
                    ->sort()
                    ->values();

                // Check for duplicate percentages
                if ($percentages->count() !== $percentages->unique()->count()) {
                    $validator->errors()->add(
                        'escalation_levels',
                        'Escalation percentages must be unique.'
                    );
                }

                // Check for logical progression
                for ($i = 1; $i < $percentages->count(); $i++) {
                    if ($percentages[$i] <= $percentages[$i - 1]) {
                        $validator->errors()->add(
                            'escalation_levels',
                            'Escalation percentages must be in ascending order.'
                        );
                        break;
                    }
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Set default values if not provided
        $this->mergeIfMissing([
            'is_default' => false,
            'is_active' => true,
            'holiday_coverage' => false,
            'exclude_weekends' => true,
            'escalation_enabled' => true,
            'notify_on_breach' => true,
            'notify_on_warning' => true,
        ]);

        // Ensure business_days is always an array
        if ($this->has('business_days') && ! is_array($this->business_days)) {
            $this->merge([
                'business_days' => explode(',', $this->business_days),
            ]);
        }

        // Convert times to proper format if needed
        if ($this->has('business_hours_start') && strlen($this->business_hours_start) === 5) {
            $this->merge(['business_hours_start' => $this->business_hours_start.':00']);
        }

        if ($this->has('business_hours_end') && strlen($this->business_hours_end) === 5) {
            $this->merge(['business_hours_end' => $this->business_hours_end.':00']);
        }
    }
}
