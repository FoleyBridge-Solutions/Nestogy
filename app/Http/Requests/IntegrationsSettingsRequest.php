<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IntegrationsSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->company_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Module Settings
            'module_enable_itdoc' => 'boolean',
            'module_enable_accounting' => 'boolean',
            'module_enable_ticketing' => 'boolean',
            'enable_alert_domain_expire' => 'boolean',

            // Automation Settings
            'enable_cron' => 'boolean',
            'recurring_auto_send_invoice' => 'boolean',
            'send_invoice_reminders' => 'boolean',
            'ticket_autoclose' => 'boolean',
            'ticket_autoclose_hours' => 'nullable|integer|min:1|max:8760',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'ticket_autoclose_hours.min' => 'Auto-close hours must be at least 1 hour.',
            'ticket_autoclose_hours.max' => 'Auto-close hours must not exceed 8760 hours (1 year).',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkboxes to boolean values
        $this->merge([
            'module_enable_itdoc' => $this->has('module_enable_itdoc'),
            'module_enable_accounting' => $this->has('module_enable_accounting'),
            'module_enable_ticketing' => $this->has('module_enable_ticketing'),
            'enable_alert_domain_expire' => $this->has('enable_alert_domain_expire'),
            'enable_cron' => $this->has('enable_cron'),
            'recurring_auto_send_invoice' => $this->has('recurring_auto_send_invoice'),
            'send_invoice_reminders' => $this->has('send_invoice_reminders'),
            'ticket_autoclose' => $this->has('ticket_autoclose'),
        ]);

        // Set default autoclose hours if ticket_autoclose is enabled but hours not provided
        if ($this->ticket_autoclose && empty($this->ticket_autoclose_hours)) {
            $this->merge(['ticket_autoclose_hours' => 72]);
        }
    }
}
