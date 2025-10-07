<?php

namespace App\Livewire\Setup\Traits;

use App\Models\Setting;
use Illuminate\Validation\Rules\Password;

trait ValidatesSteps
{
    private const RATE_VALIDATION_RULE = 'nullable|numeric|min:0|max:9999.99';

    protected function validateCurrentStep(): bool
    {
        return match ($this->currentStep) {
            1 => $this->validateCompanyInfo(),
            2 => $this->validateEmailConfig(),
            3 => $this->validateSystemPrefs(),
            4 => $this->validateMspSettings(),
            5 => $this->validateAdminUser(),
            default => true,
        };
    }

    protected function validateCompanyInfo(): bool
    {
        try {
            $this->validate([
                'company_name' => 'required|string|max:255',
                'company_email' => 'required|email|max:255',
                'currency' => 'required|string|size:3|in:USD,EUR,GBP,CAD,AUD,JPY',
                'company_phone' => 'nullable|string|max:20',
                'company_address' => 'nullable|string|max:255',
                'company_city' => 'nullable|string|max:100',
                'company_state' => 'nullable|string|max:100',
                'company_zip' => 'nullable|string|max:20',
                'company_country' => 'nullable|string|max:100',
                'company_website' => 'nullable|url|max:255',
            ], [
                'company_name.required' => 'Company name is required to set up your ERP system.',
                'company_email.required' => 'Company email is required.',
                'company_email.email' => 'Please enter a valid email address.',
                'currency.required' => 'Please select your default currency.',
                'company_website.url' => 'Please enter a valid website URL.',
            ]);

            return true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            return false;
        }
    }

    protected function validateEmailConfig(): bool
    {
        // Email configuration is optional, but if provided, validate it
        if (empty($this->smtp_host)) {
            return true; // Skip validation if no SMTP host provided
        }

        try {
            $this->validate([
                'smtp_host' => 'required|string|max:255',
                'smtp_port' => 'required|integer|min:1|max:65535',
                'smtp_encryption' => 'nullable|in:tls,ssl',
                'smtp_username' => 'nullable|string|max:255',
                'smtp_password' => 'nullable|string|max:255',
                'mail_from_email' => 'nullable|email|max:255',
                'mail_from_name' => 'nullable|string|max:255',
            ], [
                'smtp_host.required' => 'SMTP host is required for email configuration.',
                'smtp_port.required' => 'SMTP port is required.',
                'smtp_port.integer' => 'SMTP port must be a valid number.',
                'smtp_port.min' => 'SMTP port must be at least 1.',
                'smtp_port.max' => 'SMTP port cannot exceed 65535.',
                'mail_from_email.email' => 'Please enter a valid from email address.',
            ]);

            return true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            return false;
        }
    }

    protected function validateSystemPrefs(): bool
    {
        try {
            $this->validate([
                'timezone' => 'required|string|max:255',
                'date_format' => 'nullable|string|max:20',
                'theme' => 'nullable|string|in:'.implode(',', array_keys(Setting::getAvailableThemes())),
                'company_language' => 'nullable|string|size:2',
                'default_net_terms' => 'nullable|integer|min:0|max:365',
                'default_hourly_rate' => self::RATE_VALIDATION_RULE,
            ], [
                'timezone.required' => 'Please select your timezone.',
                'default_net_terms.max' => 'Payment terms cannot exceed 365 days.',
                'default_hourly_rate.numeric' => 'Hourly rate must be a valid number.',
            ]);

            return true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            return false;
        }
    }

    protected function validateMspSettings(): bool
    {
        try {
            $this->validate([
                'business_hours_start' => 'nullable|date_format:H:i',
                'business_hours_end' => 'nullable|date_format:H:i',
                'rate_standard' => self::RATE_VALIDATION_RULE,
                'rate_after_hours' => self::RATE_VALIDATION_RULE,
                'rate_emergency' => self::RATE_VALIDATION_RULE,
                'rate_weekend' => self::RATE_VALIDATION_RULE,
                'rate_holiday' => self::RATE_VALIDATION_RULE,
                'minimum_billing_increment' => 'nullable|numeric|in:0.25,0.5,1',
                'time_rounding_method' => 'nullable|string|in:nearest,up,down',
                'ticket_prefix' => 'nullable|string|max:10',
                'ticket_autoclose_hours' => 'nullable|integer|min:1|max:8760',
                'invoice_prefix' => 'nullable|string|max:10',
                'invoice_starting_number' => 'nullable|integer|min:1',
                'invoice_late_fee_percent' => 'nullable|numeric|min:0|max:100',
            ]);

            return true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            return false;
        }
    }

    protected function validateAdminUser(): bool
    {
        try {
            $this->validate([
                'admin_name' => 'required|string|max:255',
                'admin_email' => 'required|email|max:255|unique:users,email',
                'admin_password' => ['required', 'confirmed', Password::defaults()],
                'admin_password_confirmation' => 'required',
            ], [
                'admin_name.required' => 'Administrator name is required.',
                'admin_email.required' => 'Administrator email is required.',
                'admin_email.unique' => 'This email address is already in use.',
                'admin_password.required' => 'Administrator password is required.',
                'admin_password.confirmed' => 'Password confirmation does not match.',
            ]);

            return true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            return false;
        }
    }

    protected function canNavigateToStep($step): bool
    {
        // Can always go backwards
        if ($step < $this->currentStep) {
            return true;
        }

        // Can only go forward if current step is valid
        if ($step == $this->currentStep + 1) {
            return $this->validateCurrentStep();
        }

        // Can't skip steps ahead
        return false;
    }
}
