<?php

namespace App\Livewire\Setup\Traits;

trait ManagesStepData
{
    protected function saveStepData(): void
    {
        $stepData = $this->getStepData();
        session()->put("setup.step{$this->currentStep}", $stepData);
        session()->put('setup.current_step', $this->currentStep);
        session()->put('setup.completed_steps', $this->completedSteps);
    }
    
    protected function loadStepData(): void
    {
        // Restore current step and completed steps
        $this->currentStep = session()->get('setup.current_step', 1);
        $this->completedSteps = session()->get('setup.completed_steps', []);
        
        // Load data for all steps
        for ($i = 1; $i <= $this->totalSteps; $i++) {
            if ($data = session()->get("setup.step{$i}")) {
                $this->hydrateStepData($i, $data);
            }
        }
    }
    
    protected function getStepData(): array
    {
        return match($this->currentStep) {
            1 => [
                'company_name' => $this->company_name,
                'company_email' => $this->company_email,
                'company_phone' => $this->company_phone,
                'company_address' => $this->company_address,
                'company_city' => $this->company_city,
                'company_state' => $this->company_state,
                'company_zip' => $this->company_zip,
                'company_country' => $this->company_country,
                'company_website' => $this->company_website,
                'currency' => $this->currency,
            ],
            2 => [
                'smtp_host' => $this->smtp_host,
                'smtp_port' => $this->smtp_port,
                'smtp_encryption' => $this->smtp_encryption,
                'smtp_username' => $this->smtp_username,
                'smtp_password' => $this->smtp_password,
                'mail_from_email' => $this->mail_from_email,
                'mail_from_name' => $this->mail_from_name,
            ],
            3 => [
                'timezone' => $this->timezone,
                'date_format' => $this->date_format,
                'theme' => $this->theme,
                'company_language' => $this->company_language,
                'default_net_terms' => $this->default_net_terms,
                'default_hourly_rate' => $this->default_hourly_rate,
                'modules' => $this->modules,
            ],
            4 => [
                'business_hours_start' => $this->business_hours_start,
                'business_hours_end' => $this->business_hours_end,
                'rate_standard' => $this->rate_standard,
                'rate_after_hours' => $this->rate_after_hours,
                'rate_emergency' => $this->rate_emergency,
                'rate_weekend' => $this->rate_weekend,
                'rate_holiday' => $this->rate_holiday,
                'minimum_billing_increment' => $this->minimum_billing_increment,
                'time_rounding_method' => $this->time_rounding_method,
                'ticket_prefix' => $this->ticket_prefix,
                'ticket_autoclose_hours' => $this->ticket_autoclose_hours,
                'invoice_prefix' => $this->invoice_prefix,
                'invoice_starting_number' => $this->invoice_starting_number,
                'invoice_late_fee_percent' => $this->invoice_late_fee_percent,
            ],
            5 => [
                'admin_name' => $this->admin_name,
                'admin_email' => $this->admin_email,
                'admin_password' => $this->admin_password,
                'admin_password_confirmation' => $this->admin_password_confirmation,
                'enable_two_factor' => $this->enable_two_factor,
                'enable_audit_logging' => $this->enable_audit_logging,
            ],
            default => [],
        };
    }
    
    protected function hydrateStepData(int $step, array $data): void
    {
        switch ($step) {
            case 1:
                $this->company_name = $data['company_name'] ?? '';
                $this->company_email = $data['company_email'] ?? '';
                $this->company_phone = $data['company_phone'] ?? '';
                $this->company_address = $data['company_address'] ?? '';
                $this->company_city = $data['company_city'] ?? '';
                $this->company_state = $data['company_state'] ?? '';
                $this->company_zip = $data['company_zip'] ?? '';
                $this->company_country = $data['company_country'] ?? 'United States';
                $this->company_website = $data['company_website'] ?? '';
                $this->currency = $data['currency'] ?? 'USD';
                break;
                
            case 2:
                $this->smtp_host = $data['smtp_host'] ?? '';
                $this->smtp_port = $data['smtp_port'] ?? '587';
                $this->smtp_encryption = $data['smtp_encryption'] ?? 'tls';
                $this->smtp_username = $data['smtp_username'] ?? '';
                $this->smtp_password = $data['smtp_password'] ?? '';
                $this->mail_from_email = $data['mail_from_email'] ?? '';
                $this->mail_from_name = $data['mail_from_name'] ?? '';
                break;
                
            case 3:
                $this->timezone = $data['timezone'] ?? 'America/New_York';
                $this->date_format = $data['date_format'] ?? 'Y-m-d';
                $this->theme = $data['theme'] ?? 'blue';
                $this->company_language = $data['company_language'] ?? 'en';
                $this->default_net_terms = $data['default_net_terms'] ?? 30;
                $this->default_hourly_rate = $data['default_hourly_rate'] ?? 150;
                $this->modules = $data['modules'] ?? [
                    'ticketing' => true,
                    'invoicing' => true,
                    'assets' => true,
                    'projects' => true,
                    'contracts' => true,
                    'reporting' => true,
                ];
                break;
                
            case 4:
                $this->business_hours_start = $data['business_hours_start'] ?? '09:00';
                $this->business_hours_end = $data['business_hours_end'] ?? '17:00';
                $this->rate_standard = $data['rate_standard'] ?? 150;
                $this->rate_after_hours = $data['rate_after_hours'] ?? 225;
                $this->rate_emergency = $data['rate_emergency'] ?? 300;
                $this->rate_weekend = $data['rate_weekend'] ?? 200;
                $this->rate_holiday = $data['rate_holiday'] ?? 250;
                $this->minimum_billing_increment = $data['minimum_billing_increment'] ?? 0.25;
                $this->time_rounding_method = $data['time_rounding_method'] ?? 'nearest';
                $this->ticket_prefix = $data['ticket_prefix'] ?? 'TKT-';
                $this->ticket_autoclose_hours = $data['ticket_autoclose_hours'] ?? 72;
                $this->invoice_prefix = $data['invoice_prefix'] ?? 'INV-';
                $this->invoice_starting_number = $data['invoice_starting_number'] ?? 1000;
                $this->invoice_late_fee_percent = $data['invoice_late_fee_percent'] ?? 1.5;
                break;
                
            case 5:
                $this->admin_name = $data['admin_name'] ?? '';
                $this->admin_email = $data['admin_email'] ?? '';
                $this->admin_password = $data['admin_password'] ?? '';
                $this->admin_password_confirmation = $data['admin_password_confirmation'] ?? '';
                $this->enable_two_factor = $data['enable_two_factor'] ?? false;
                $this->enable_audit_logging = $data['enable_audit_logging'] ?? true;
                break;
        }
    }
    
    protected function clearSetupSession(): void
    {
        for ($i = 1; $i <= $this->totalSteps; $i++) {
            session()->forget("setup.step{$i}");
        }
        session()->forget('setup.current_step');
        session()->forget('setup.completed_steps');
    }
    
    protected function loadDefaults(): void
    {
        // Set default values for all properties
        $this->company_name = '';
        $this->company_email = '';
        $this->company_phone = '';
        $this->company_address = '';
        $this->company_city = '';
        $this->company_state = '';
        $this->company_zip = '';
        $this->company_country = 'United States';
        $this->company_website = '';
        $this->currency = 'USD';
        
        $this->smtp_host = '';
        $this->smtp_port = '587';
        $this->smtp_encryption = 'tls';
        $this->smtp_username = '';
        $this->smtp_password = '';
        $this->mail_from_email = '';
        $this->mail_from_name = '';
        
        $this->timezone = 'America/New_York';
        $this->date_format = 'Y-m-d';
        $this->theme = 'blue';
        $this->company_language = 'en';
        $this->default_net_terms = 30;
        $this->default_hourly_rate = 150;
        $this->modules = [
            'ticketing' => true,
            'invoicing' => true,
            'assets' => true,
            'projects' => true,
            'contracts' => true,
            'reporting' => true,
        ];
        
        $this->business_hours_start = '09:00';
        $this->business_hours_end = '17:00';
        $this->rate_standard = 150;
        $this->rate_after_hours = 225;
        $this->rate_emergency = 300;
        $this->rate_weekend = 200;
        $this->rate_holiday = 250;
        $this->minimum_billing_increment = 0.25;
        $this->time_rounding_method = 'nearest';
        $this->ticket_prefix = 'TKT-';
        $this->ticket_autoclose_hours = 72;
        $this->invoice_prefix = 'INV-';
        $this->invoice_starting_number = 1000;
        $this->invoice_late_fee_percent = 1.5;
        
        $this->admin_name = '';
        $this->admin_email = '';
        $this->admin_password = '';
        $this->admin_password_confirmation = '';
        $this->enable_two_factor = false;
        $this->enable_audit_logging = true;
    }
}