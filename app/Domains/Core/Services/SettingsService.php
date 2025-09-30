<?php

namespace App\Domains\Core\Services;

use App\Models\Company;
use App\Models\CompanyCustomization;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SettingsService
{
    /**
     * Update company and settings data
     * 
     * @param Company $company
     * @param array $data
     * @return bool
     */
    public function updateSettings(Company $company, array $data): bool
    {
        try {
            DB::beginTransaction();
            
            // Update company fields
            $company->update([
                'name' => $data['company_name'],
                'currency' => $data['currency'],
            ]);
            
            // Update or create settings
            $setting = $company->setting;
            
            if (!$setting) {
                // Create settings if they don't exist
                $setting = Setting::create([
                    'company_id' => $company->id,
                    'current_database_version' => '1.0.0',
                    'start_page' => 'dashboard',
                    'theme' => 'blue',
                    'timezone' => $data['timezone'],
                    'date_format' => $data['date_format'],
                ]);
            } else {
                // Update existing settings
                $setting->update([
                    'timezone' => $data['timezone'],
                    'date_format' => $data['date_format'],
                ]);
            }
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update settings: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get current settings for a company
     * 
     * @param Company $company
     * @return array
     */
    public function getSettings(Company $company): array
    {
        $setting = $company->setting;
        
        return [
            'company_name' => $company->name,
            'currency' => $company->currency ?? 'USD',
            'timezone' => $setting?->timezone ?? 'UTC',
            'date_format' => $setting?->date_format ?? 'Y-m-d',
        ];
    }
    
    /**
     * Get available date formats
     * 
     * @return array
     */
    public static function getDateFormats(): array
    {
        return [
            'Y-m-d' => 'YYYY-MM-DD (2024-12-31)',
            'm/d/Y' => 'MM/DD/YYYY (12/31/2024)',
            'd/m/Y' => 'DD/MM/YYYY (31/12/2024)',
            'M d, Y' => 'Mon DD, YYYY (Dec 31, 2024)',
            'd M Y' => 'DD Mon YYYY (31 Dec 2024)',
        ];
    }
    
    /**
     * Get available timezones
     * 
     * @return array
     */
    public static function getTimezones(): array
    {
        return [
            'UTC' => 'UTC',
            'America/New_York' => 'Eastern Time (US & Canada)',
            'America/Chicago' => 'Central Time (US & Canada)',
            'America/Denver' => 'Mountain Time (US & Canada)',
            'America/Los_Angeles' => 'Pacific Time (US & Canada)',
            'America/Phoenix' => 'Arizona',
            'America/Anchorage' => 'Alaska',
            'Pacific/Honolulu' => 'Hawaii',
            'Europe/London' => 'London',
            'Europe/Paris' => 'Paris',
            'Europe/Berlin' => 'Berlin',
            'Europe/Moscow' => 'Moscow',
            'Asia/Tokyo' => 'Tokyo',
            'Asia/Shanghai' => 'Beijing',
            'Asia/Singapore' => 'Singapore',
            'Asia/Dubai' => 'Dubai',
            'Australia/Sydney' => 'Sydney',
            'Australia/Melbourne' => 'Melbourne',
            'Pacific/Auckland' => 'Auckland',
        ];
    }
    
    /**
     * Get available currencies
     * 
     * @return array
     */
    public static function getCurrencies(): array
    {
        return [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'CAD' => 'Canadian Dollar',
            'AUD' => 'Australian Dollar',
            'JPY' => 'Japanese Yen',
            'CHF' => 'Swiss Franc',
            'CNY' => 'Chinese Yuan',
            'INR' => 'Indian Rupee',
            'BRL' => 'Brazilian Real',
            'MXN' => 'Mexican Peso',
            'KRW' => 'South Korean Won',
            'SGD' => 'Singapore Dollar',
            'HKD' => 'Hong Kong Dollar',
            'SEK' => 'Swedish Krona',
            'NOK' => 'Norwegian Krone',
            'DKK' => 'Danish Krone',
            'PLN' => 'Polish Złoty',
            'CZK' => 'Czech Koruna',
            'HUF' => 'Hungarian Forint',
        ];
    }
    
    /**
     * Export settings to JSON
     * 
     * @param Company $company
     * @return string
     */
    public function exportSettings(Company $company): string
    {
        $settings = $this->getComprehensiveSettings($company);
        
        $exportData = [
            'company_name' => $company->name,
            'export_date' => now()->toISOString(),
            'version' => '1.0',
            'settings' => $settings,
        ];
        
        return json_encode($exportData, JSON_PRETTY_PRINT);
    }
    
    /**
     * Import settings from JSON
     * 
     * @param Company $company
     * @param string $jsonData
     * @return bool
     */
    public function importSettings(Company $company, string $jsonData): bool
    {
        try {
            $data = json_decode($jsonData, true);
            
            if (!$data || !isset($data['settings'])) {
                throw new \Exception('Invalid settings data format');
            }
            
            DB::beginTransaction();
            
            $setting = $company->setting;
            if (!$setting) {
                $setting = Setting::create([
                    'company_id' => $company->id,
                    'current_database_version' => '1.0.0',
                    'start_page' => 'dashboard',
                    'theme' => 'blue',
                    'timezone' => 'UTC',
                ]);
            }
            
            // Update settings based on imported data
            $importedSettings = $data['settings'];
            $this->updateGeneralSettings($setting, $importedSettings);
            $this->updateSecuritySettings($setting, $importedSettings);
            $this->updateEmailSettings($setting, $importedSettings);
            $this->updateBillingFinancialSettings($setting, $importedSettings);
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to import settings: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get settings template for MSP type
     * 
     * @param string $mspType
     * @return array
     */
    public function getSettingsTemplate(string $mspType): array
    {
        $templates = [
            'small_msp' => [
                'name' => 'Small MSP (1-50 endpoints)',
                'settings' => [
                    'multi_currency_enabled' => false,
                    'recurring_billing_enabled' => true,
                    'time_tracking_enabled' => true,
                    'customer_satisfaction_enabled' => false,
                    'soc2_compliance_enabled' => false,
                    'hipaa_compliance_enabled' => false,
                    'pci_compliance_enabled' => false,
                    'auto_create_tickets_from_alerts' => true,
                    'session_timeout_minutes' => 480,
                    'audit_retention_days' => 365,
                    'password_min_length' => 8,
                    'two_factor_enabled' => false,
                ]
            ],
            'medium_msp' => [
                'name' => 'Medium MSP (50-500 endpoints)',
                'settings' => [
                    'multi_currency_enabled' => false,
                    'recurring_billing_enabled' => true,
                    'time_tracking_enabled' => true,
                    'customer_satisfaction_enabled' => true,
                    'soc2_compliance_enabled' => true,
                    'hipaa_compliance_enabled' => false,
                    'pci_compliance_enabled' => false,
                    'auto_create_tickets_from_alerts' => true,
                    'session_timeout_minutes' => 240,
                    'audit_retention_days' => 1095,
                    'password_min_length' => 10,
                    'two_factor_enabled' => true,
                ]
            ],
            'large_msp' => [
                'name' => 'Large MSP (500+ endpoints)',
                'settings' => [
                    'multi_currency_enabled' => true,
                    'recurring_billing_enabled' => true,
                    'time_tracking_enabled' => true,
                    'customer_satisfaction_enabled' => true,
                    'soc2_compliance_enabled' => true,
                    'hipaa_compliance_enabled' => true,
                    'pci_compliance_enabled' => true,
                    'auto_create_tickets_from_alerts' => true,
                    'session_timeout_minutes' => 120,
                    'audit_retention_days' => 2555,
                    'password_min_length' => 12,
                    'two_factor_enabled' => true,
                ]
            ],
            'healthcare_msp' => [
                'name' => 'Healthcare MSP',
                'settings' => [
                    'multi_currency_enabled' => false,
                    'recurring_billing_enabled' => true,
                    'time_tracking_enabled' => true,
                    'customer_satisfaction_enabled' => true,
                    'soc2_compliance_enabled' => true,
                    'hipaa_compliance_enabled' => true,
                    'pci_compliance_enabled' => false,
                    'auto_create_tickets_from_alerts' => true,
                    'session_timeout_minutes' => 60,
                    'audit_retention_days' => 2555,
                    'password_min_length' => 14,
                    'two_factor_enabled' => true,
                    'force_single_session' => true,
                    'audit_logging_enabled' => true,
                ]
            ],
            'financial_msp' => [
                'name' => 'Financial Services MSP',
                'settings' => [
                    'multi_currency_enabled' => true,
                    'recurring_billing_enabled' => true,
                    'time_tracking_enabled' => true,
                    'customer_satisfaction_enabled' => true,
                    'soc2_compliance_enabled' => true,
                    'hipaa_compliance_enabled' => false,
                    'pci_compliance_enabled' => true,
                    'auto_create_tickets_from_alerts' => true,
                    'session_timeout_minutes' => 30,
                    'audit_retention_days' => 2555,
                    'password_min_length' => 16,
                    'two_factor_enabled' => true,
                    'force_single_session' => true,
                    'audit_logging_enabled' => true,
                    'geo_blocking_enabled' => true,
                ]
            ]
        ];
        
        return $templates[$mspType] ?? $templates['small_msp'];
    }
    
    /**
     * Apply settings template
     * 
     * @param Company $company
     * @param string $templateType
     * @return bool
     */
    public function applySettingsTemplate(Company $company, string $templateType): bool
    {
        try {
            $template = $this->getSettingsTemplate($templateType);
            $setting = $company->setting;
            
            if (!$setting) {
                $setting = Setting::create([
                    'company_id' => $company->id,
                    'current_database_version' => '1.0.0',
                    'start_page' => 'dashboard',
                    'theme' => 'blue',
                    'timezone' => 'UTC',
                ]);
            }
            
            $setting->update($template['settings']);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to apply settings template: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update security settings
     * 
     * @param Setting $setting
     * @param array $data
     * @return bool
     */
    public function updateSecuritySettings(Setting $setting, array $data): bool
    {
        try {
            $updateData = [
                'client_portal_enable' => $data['client_portal_enable'] ?? false,
                'login_message' => $data['login_message'] ?? null,
                'login_key_required' => $data['login_key_required'] ?? false,
                'destructive_deletes_enable' => $data['destructive_deletes_enable'] ?? false,
            ];
            
            // Only update login_key_secret if provided
            if (!empty($data['login_key_secret'])) {
                $updateData['login_key_secret'] = bcrypt($data['login_key_secret']);
            }
            
            $setting->update($updateData);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update security settings: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update email settings
     * 
     * @param Setting $setting
     * @param array $data
     * @return bool
     */
    public function updateEmailSettings(Setting $setting, array $data): bool
    {
        try {
            $updateData = [
                'smtp_host' => $data['smtp_host'] ?? null,
                'smtp_port' => $data['smtp_port'] ?? null,
                'smtp_encryption' => $data['smtp_encryption'] ?? null,
                'smtp_auth_method' => $data['smtp_auth_method'] ?? 'password',
                'smtp_username' => $data['smtp_username'] ?? null,
                'mail_from_email' => $data['mail_from_email'] ?? null,
                'mail_from_name' => $data['mail_from_name'] ?? null,
                'imap_host' => $data['imap_host'] ?? null,
                'imap_port' => $data['imap_port'] ?? null,
                'imap_encryption' => $data['imap_encryption'] ?? null,
                'imap_auth_method' => $data['imap_auth_method'] ?? 'password',
                'imap_username' => $data['imap_username'] ?? null,
                'ticket_email_parse' => $data['ticket_email_parse'] ?? false,
                'ticket_new_ticket_notification_email' => $data['ticket_new_ticket_notification_email'] ?? null,
            ];
            
            // Only update passwords if provided
            if (!empty($data['smtp_password'])) {
                $updateData['smtp_password'] = encrypt($data['smtp_password']);
            }
            
            if (!empty($data['imap_password'])) {
                $updateData['imap_password'] = encrypt($data['imap_password']);
            }
            
            $setting->update($updateData);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update email settings: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update integrations settings
     * 
     * @param Setting $setting
     * @param array $data
     * @return bool
     */
    public function updateIntegrationsSettings(Setting $setting, array $data): bool
    {
        try {
            DB::beginTransaction();
            
            $updateData = [
                'module_enable_itdoc' => $data['module_enable_itdoc'] ?? false,
                'module_enable_accounting' => $data['module_enable_accounting'] ?? false,
                'module_enable_ticketing' => $data['module_enable_ticketing'] ?? false,
                'enable_alert_domain_expire' => $data['enable_alert_domain_expire'] ?? false,
                'enable_cron' => $data['enable_cron'] ?? false,
                'recurring_auto_send_invoice' => $data['recurring_auto_send_invoice'] ?? false,
                'send_invoice_reminders' => $data['send_invoice_reminders'] ?? false,
                'ticket_autoclose' => $data['ticket_autoclose'] ?? false,
                'ticket_autoclose_hours' => $data['ticket_autoclose_hours'] ?? 72,
            ];
            
            // Generate cron key if cron is enabled and no key exists
            if ($updateData['enable_cron'] && empty($setting->cron_key)) {
                $updateData['cron_key'] = bin2hex(random_bytes(32));
            }
            
            $setting->update($updateData);
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update integrations settings: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update general settings
     * 
     * @param Setting $setting
     * @param array $data
     * @return bool
     */
    public function updateGeneralSettings(Setting $setting, array $data): bool
    {
        try {
            DB::beginTransaction();
            
            // Update company information
            $company = $setting->company;
            $companyData = [
                'name' => $data['company_name'],
                'currency' => $data['company_currency'] ?? 'USD',
            ];
            
            // Add company address and contact fields if they exist in the data
            if (isset($data['business_address'])) {
                $companyData['address'] = $data['business_address'];
            }
            if (isset($data['business_phone'])) {
                $companyData['phone'] = $data['business_phone'];
            }
            if (isset($data['business_email'])) {
                $companyData['email'] = $data['business_email'];
            }
            if (isset($data['website'])) {
                $companyData['website'] = $data['website'];
            }
            if (isset($data['company_logo'])) {
                $companyData['logo'] = $data['company_logo'];
            }
            
            $company->update($companyData);
            
            $updateData = [
                'company_logo' => $data['company_logo'] ?? null,
                'company_colors' => $data['company_colors'] ?? null,
                'company_address' => $data['business_address'] ?? null,  // Map business_address to company_address in settings
                'company_phone' => $data['business_phone'] ?? null,
                'company_website' => $data['website'] ?? null,
                'company_tax_id' => $data['tax_id'] ?? null,
                'business_hours' => $data['business_hours'] ?? null,
                'company_holidays' => $data['company_holidays'] ?? null,
                'company_language' => $data['company_language'] ?? 'en',
                'company_currency' => $data['company_currency'] ?? 'USD',
                'custom_fields' => $data['custom_fields'] ?? null,
                'localization_settings' => $data['localization_settings'] ?? null,
                'timezone' => $data['timezone'],
                'date_format' => $data['date_format'],
                'theme' => $data['theme'],
                'start_page' => $data['start_page'] ?? 'dashboard',
            ];
            
            $setting->update($updateData);
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update general settings', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            return false;
        }
    }
    
    /**
     * Update billing and financial settings
     * 
     * @param Setting $setting
     * @param array $data
     * @return bool
     */
    public function updateBillingFinancialSettings(Setting $setting, array $data): bool
    {
        try {
            DB::beginTransaction();
            
            Log::info('SettingsService: Starting updateBillingFinancialSettings', [
                'setting_id' => $setting->id,
                'company_id' => $setting->company_id,
                'incoming_data' => $data
            ]);
            
            // Build payment gateway settings from individual form fields
            $paypalSettings = null;
            if (isset($data['paypal_enabled']) || isset($data['paypal_client_id']) || isset($data['paypal_client_secret'])) {
                $paypalSettings = [
                    'enabled' => filter_var($data['paypal_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'client_id' => $data['paypal_client_id'] ?? null,
                ];
                if (!empty($data['paypal_client_secret'])) {
                    $paypalSettings['client_secret'] = $data['paypal_client_secret'];
                }
            }

            $stripeSettings = null;
            if (isset($data['stripe_enabled']) || isset($data['stripe_publishable_key']) || isset($data['stripe_secret_key'])) {
                $stripeSettings = [
                    'enabled' => filter_var($data['stripe_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'publishable_key' => $data['stripe_publishable_key'] ?? null,
                ];
                if (!empty($data['stripe_secret_key'])) {
                    $stripeSettings['secret_key'] = $data['stripe_secret_key'];
                }
            }

            $achSettings = null;
            if (isset($data['ach_enabled']) || isset($data['ach_bank_name']) || isset($data['ach_routing_number']) || isset($data['ach_account_number'])) {
                $achSettings = [
                    'enabled' => filter_var($data['ach_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'bank_name' => $data['ach_bank_name'] ?? null,
                    'routing_number' => $data['ach_routing_number'] ?? null,
                    'account_number' => $data['ach_account_number'] ?? null,
                ];
            }

            $wireSettings = null;
            if (isset($data['wire_enabled']) || isset($data['wire_bank_name']) || isset($data['wire_swift_code']) || isset($data['wire_account_number'])) {
                $wireSettings = [
                    'enabled' => filter_var($data['wire_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'bank_name' => $data['wire_bank_name'] ?? null,
                    'swift_code' => $data['wire_swift_code'] ?? null,
                    'account_number' => $data['wire_account_number'] ?? null,
                ];
            }

            $checkSettings = null;
            if (isset($data['check_enabled']) || isset($data['check_payto_name']) || isset($data['check_mailing_address'])) {
                $checkSettings = [
                    'enabled' => filter_var($data['check_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'payto_name' => $data['check_payto_name'] ?? null,
                    'mailing_address' => $data['check_mailing_address'] ?? null,
                ];
            }

            $updateData = [
                'multi_currency_enabled' => $data['multi_currency_enabled'] ?? false,
                'supported_currencies' => $data['supported_currencies'] ?? null,
                'exchange_rate_provider' => $data['exchange_rate_provider'] ?? null,
                'auto_update_exchange_rates' => $data['auto_update_exchange_rates'] ?? true,
                'tax_calculation_settings' => $data['tax_calculation_settings'] ?? null,
                'tax_engine_provider' => $data['tax_engine_provider'] ?? null,
                'payment_gateway_settings' => $data['payment_gateway_settings'] ?? null,
                'stripe_settings' => $stripeSettings ?? $data['stripe_settings'] ?? null,
                'square_settings' => $data['square_settings'] ?? null,
                'paypal_settings' => $paypalSettings ?? $data['paypal_settings'] ?? null,
                'authorize_net_settings' => $data['authorize_net_settings'] ?? null,
                'ach_settings' => $achSettings ?? $data['ach_settings'] ?? null,
                'wire_settings' => $wireSettings ?? $data['wire_settings'] ?? null,
                'check_settings' => $checkSettings ?? $data['check_settings'] ?? null,
                'recurring_billing_enabled' => $data['recurring_billing_enabled'] ?? true,
                'recurring_billing_settings' => $data['recurring_billing_settings'] ?? null,
                'late_fee_settings' => $data['late_fee_settings'] ?? null,
                'collection_settings' => $data['collection_settings'] ?? null,
                'accounting_integration_settings' => $data['accounting_integration_settings'] ?? null,
                'quickbooks_settings' => $data['quickbooks_settings'] ?? null,
                'xero_settings' => $data['xero_settings'] ?? null,
                'sage_settings' => $data['sage_settings'] ?? null,
                'revenue_recognition_enabled' => $data['revenue_recognition_enabled'] ?? false,
                'revenue_recognition_settings' => $data['revenue_recognition_settings'] ?? null,
                'purchase_order_settings' => $data['purchase_order_settings'] ?? null,
                'expense_approval_settings' => $data['expense_approval_settings'] ?? null,
                'invoice_prefix' => $data['invoice_prefix'] ?? null,
                'invoice_next_number' => $data['invoice_next_number'] ?? 1,
                'invoice_footer' => $data['invoice_footer'] ?? null,
                'invoice_from_name' => $data['invoice_from_name'] ?? null,
                'invoice_from_email' => $data['invoice_from_email'] ?? null,
                'invoice_late_fee_enable' => $data['invoice_late_fee_enable'] ?? false,
                'invoice_late_fee_percent' => $data['invoice_late_fee_percent'] ?? 0,
                'quote_prefix' => $data['quote_prefix'] ?? null,
                'quote_next_number' => $data['quote_next_number'] ?? 1,
                'quote_footer' => $data['quote_footer'] ?? null,
                'quote_from_name' => $data['quote_from_name'] ?? null,
                'quote_from_email' => $data['quote_from_email'] ?? null,
                'default_transfer_from_account' => $data['default_transfer_from_account'] ?? null,
                'default_transfer_to_account' => $data['default_transfer_to_account'] ?? null,
                'default_payment_account' => $data['default_payment_account'] ?? null,
                'default_expense_account' => $data['default_expense_account'] ?? null,
                'default_payment_method' => $data['default_payment_method'] ?? null,
                'default_expense_payment_method' => $data['default_expense_payment_method'] ?? null,
                'default_net_terms' => $data['default_net_terms'] ?? 30,
                'default_hourly_rate' => $data['default_hourly_rate'] ?? 0,
                'profitability_tracking_settings' => $data['profitability_tracking_settings'] ?? null,
                'recurring_auto_send_invoice' => $data['recurring_auto_send_invoice'] ?? true,
                'send_invoice_reminders' => $data['send_invoice_reminders'] ?? true,
                'invoice_overdue_reminders' => $data['invoice_overdue_reminders'] ?? null,
            ];
            
            Log::info('SettingsService: About to update setting', [
                'setting_id' => $setting->id,
                'update_data' => $updateData
            ]);
            
            $updateResult = $setting->update($updateData);
            
            Log::info('SettingsService: Update result', [
                'update_result' => $updateResult,
                'setting_id' => $setting->id
            ]);
            
            if (!$updateResult) {
                DB::rollBack();
                Log::error('Failed to update billing/financial settings: update returned false', [
                    'setting_id' => $setting->id,
                    'company_id' => $setting->company_id,
                    'data_keys' => array_keys($data)
                ]);
                return false;
            }
            
            // Verify the data was actually saved
            $setting->refresh();
            Log::info('SettingsService: Post-save verification', [
                'setting_id' => $setting->id,
                'paypal_settings' => $setting->paypal_settings,
                'ach_settings' => $setting->ach_settings,
                'wire_settings' => $setting->wire_settings,
                'check_settings' => $setting->check_settings,
                'paypal_enabled' => $setting->paypal_enabled,
                'ach_enabled' => $setting->ach_enabled,
                'wire_enabled' => $setting->wire_enabled,
                'check_enabled' => $setting->check_enabled
            ]);
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update billing/financial settings: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'setting_id' => $setting->id ?? null,
                'company_id' => $setting->company_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Update RMM and monitoring settings
     * 
     * @param Setting $setting
     * @param array $data
     * @return bool
     */
    public function updateRmmMonitoringSettings(Setting $setting, array $data): bool
    {
        try {
            DB::beginTransaction();
            
            $updateData = [
                'connectwise_automate_settings' => $data['connectwise_automate_settings'] ?? null,
                'datto_rmm_settings' => $data['datto_rmm_settings'] ?? null,
                'ninja_rmm_settings' => $data['ninja_rmm_settings'] ?? null,
                'kaseya_vsa_settings' => $data['kaseya_vsa_settings'] ?? null,
                'auvik_settings' => $data['auvik_settings'] ?? null,
                'prtg_settings' => $data['prtg_settings'] ?? null,
                'solarwinds_settings' => $data['solarwinds_settings'] ?? null,
                'monitoring_alert_thresholds' => $data['monitoring_alert_thresholds'] ?? null,
                'escalation_rules' => $data['escalation_rules'] ?? null,
                'asset_discovery_settings' => $data['asset_discovery_settings'] ?? null,
                'patch_management_settings' => $data['patch_management_settings'] ?? null,
                'remote_access_settings' => $data['remote_access_settings'] ?? null,
                'auto_create_tickets_from_alerts' => $data['auto_create_tickets_from_alerts'] ?? false,
                'alert_to_ticket_mapping' => $data['alert_to_ticket_mapping'] ?? null,
            ];
            
            $setting->update($updateData);
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update RMM/monitoring settings: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update ticketing and service desk settings
     * 
     * @param Setting $setting
     * @param array $data
     * @return bool
     */
    public function updateTicketingServiceDeskSettings(Setting $setting, array $data): bool
    {
        try {
            DB::beginTransaction();
            
            $updateData = [
                'ticket_prefix' => $data['ticket_prefix'] ?? null,
                'ticket_next_number' => $data['ticket_next_number'] ?? 1,
                'ticket_from_name' => $data['ticket_from_name'] ?? null,
                'ticket_from_email' => $data['ticket_from_email'] ?? null,
                'ticket_email_parse' => $data['ticket_email_parse'] ?? false,
                'ticket_client_general_notifications' => $data['ticket_client_general_notifications'] ?? true,
                'ticket_autoclose' => $data['ticket_autoclose'] ?? false,
                'ticket_autoclose_hours' => $data['ticket_autoclose_hours'] ?? 72,
                'ticket_new_ticket_notification_email' => $data['ticket_new_ticket_notification_email'] ?? null,
                'ticket_categorization_rules' => $data['ticket_categorization_rules'] ?? null,
                'ticket_priority_rules' => $data['ticket_priority_rules'] ?? null,
                'sla_definitions' => $data['sla_definitions'] ?? null,
                'sla_escalation_policies' => $data['sla_escalation_policies'] ?? null,
                'auto_assignment_rules' => $data['auto_assignment_rules'] ?? null,
                'routing_logic' => $data['routing_logic'] ?? null,
                'approval_workflows' => $data['approval_workflows'] ?? null,
                'time_tracking_enabled' => $data['time_tracking_enabled'] ?? true,
                'time_tracking_settings' => $data['time_tracking_settings'] ?? null,
                'customer_satisfaction_enabled' => $data['customer_satisfaction_enabled'] ?? false,
                'csat_settings' => $data['csat_settings'] ?? null,
                'ticket_templates' => $data['ticket_templates'] ?? null,
                'ticket_automation_rules' => $data['ticket_automation_rules'] ?? null,
                'multichannel_settings' => $data['multichannel_settings'] ?? null,
                'queue_management_settings' => $data['queue_management_settings'] ?? null,
            ];
            
            $setting->update($updateData);
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update ticketing/service desk settings: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update compliance and audit settings
     * 
     * @param Setting $setting
     * @param array $data
     * @return bool
     */
    public function updateComplianceAuditSettings(Setting $setting, array $data): bool
    {
        try {
            DB::beginTransaction();
            
            $updateData = [
                'soc2_compliance_enabled' => $data['soc2_compliance_enabled'] ?? false,
                'soc2_settings' => $data['soc2_settings'] ?? null,
                'hipaa_compliance_enabled' => $data['hipaa_compliance_enabled'] ?? false,
                'hipaa_settings' => $data['hipaa_settings'] ?? null,
                'pci_compliance_enabled' => $data['pci_compliance_enabled'] ?? false,
                'pci_settings' => $data['pci_settings'] ?? null,
                'gdpr_compliance_enabled' => $data['gdpr_compliance_enabled'] ?? false,
                'gdpr_settings' => $data['gdpr_settings'] ?? null,
                'industry_compliance_settings' => $data['industry_compliance_settings'] ?? null,
                'data_retention_policies' => $data['data_retention_policies'] ?? null,
                'data_destruction_policies' => $data['data_destruction_policies'] ?? null,
                'risk_assessment_settings' => $data['risk_assessment_settings'] ?? null,
                'vendor_compliance_settings' => $data['vendor_compliance_settings'] ?? null,
                'incident_response_settings' => $data['incident_response_settings'] ?? null,
                'audit_logging_enabled' => $data['audit_logging_enabled'] ?? true,
                'audit_retention_days' => $data['audit_retention_days'] ?? 365,
            ];
            
            $setting->update($updateData);
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update compliance/audit settings: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user management settings
     * 
     * @param Setting $setting
     * @param array $data
     * @return bool
     */
    public function updateUserManagementSettings(Setting $setting, array $data): bool
    {
        try {
            DB::beginTransaction();
            
            // Note: User management settings might be stored in a separate table
            // For now, we'll store them in JSON fields in the settings table
            $updateData = [
                'user_management_settings' => [
                    'max_users' => $data['max_users'] ?? null,
                    'user_invite_limit_per_month' => $data['user_invite_limit_per_month'] ?? 50,
                    'require_admin_approval_for_new_users' => $data['require_admin_approval_for_new_users'] ?? false,
                    'auto_deactivate_unused_accounts_days' => $data['auto_deactivate_unused_accounts_days'] ?? null,
                    'user_onboarding_settings' => $data['user_onboarding_settings'] ?? null,
                    'user_profile_settings' => $data['user_profile_settings'] ?? null,
                    'authentication_settings' => $data['authentication_settings'] ?? null,
                    'role_permission_settings' => $data['role_permission_settings'] ?? null,
                    'session_management_settings' => $data['session_management_settings'] ?? null,
                    'user_activity_settings' => $data['user_activity_settings'] ?? null,
                    'user_offboarding_settings' => $data['user_offboarding_settings'] ?? null,
                    'department_settings' => $data['department_settings'] ?? null,
                    'user_communication_settings' => $data['user_communication_settings'] ?? null,
                    'skill_management_settings' => $data['skill_management_settings'] ?? null,
                    'performance_settings' => $data['performance_settings'] ?? null,
                    'user_time_tracking_settings' => $data['user_time_tracking_settings'] ?? null,
                    'workspace_settings' => $data['workspace_settings'] ?? null,
                    'emergency_settings' => $data['emergency_settings'] ?? null,
                ],
            ];
            
            $setting->update($updateData);
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update user management settings: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get comprehensive settings for a company
     * 
     * @param Company $company
     * @return array
     */
    public function getComprehensiveSettings(Company $company): array
    {
        $setting = $company->setting;
        
        if (!$setting) {
            // Return default settings if none exist
            return $this->getDefaultSettings();
        }
        
        return [
            // General & Company
            'company_name' => $company->name,
            'company_logo' => $setting->company_logo,
            'company_colors' => $setting->company_colors,
            'company_address' => $setting->company_address,
            'business_address' => $setting->company_address,  // Map for form compatibility
            'business_phone' => $setting->company_phone,      // Map for form compatibility
            'business_email' => $company->email,              // Use company email
            'website' => $setting->company_website,           // Map for form compatibility
            'tax_id' => $setting->company_tax_id,             // Map for form compatibility
            'company_city' => $setting->company_city,
            'company_state' => $setting->company_state,
            'company_zip' => $setting->company_zip,
            'company_country' => $setting->company_country ?? 'US',
            'company_phone' => $setting->company_phone,
            'company_website' => $setting->company_website,
            'company_tax_id' => $setting->company_tax_id,
            'business_hours' => $setting->business_hours,
            'company_holidays' => $setting->company_holidays,
            'company_language' => $setting->company_language ?? 'en',
            'company_currency' => $setting->company_currency ?? 'USD',
            'custom_fields' => $setting->custom_fields,
            'localization_settings' => $setting->localization_settings,
            'timezone' => $setting->timezone ?? 'UTC',
            'date_format' => $setting->date_format ?? 'Y-m-d',
            'theme' => $setting->theme ?? 'blue',
            'start_page' => $setting->start_page ?? 'dashboard',
            
            // Security
            'password_min_length' => $setting->password_min_length ?? 8,
            'password_require_special' => $setting->password_require_special ?? true,
            'password_require_numbers' => $setting->password_require_numbers ?? true,
            'password_require_uppercase' => $setting->password_require_uppercase ?? true,
            'password_expiry_days' => $setting->password_expiry_days ?? 90,
            'password_history_count' => $setting->password_history_count ?? 5,
            'two_factor_enabled' => $setting->two_factor_enabled ?? false,
            'two_factor_methods' => $setting->two_factor_methods,
            'session_timeout_minutes' => $setting->session_timeout_minutes ?? 480,
            'force_single_session' => $setting->force_single_session ?? false,
            'max_login_attempts' => $setting->max_login_attempts ?? 5,
            'lockout_duration_minutes' => $setting->lockout_duration_minutes ?? 15,
            'allowed_ip_ranges' => $setting->allowed_ip_ranges,
            'blocked_ip_ranges' => $setting->blocked_ip_ranges,
            'geo_blocking_enabled' => $setting->geo_blocking_enabled ?? false,
            'allowed_countries' => $setting->allowed_countries,
            'sso_settings' => $setting->sso_settings,
            'audit_logging_enabled' => $setting->audit_logging_enabled ?? true,
            'audit_retention_days' => $setting->audit_retention_days ?? 365,
            'login_message' => $setting->login_message,
            'login_key_required' => $setting->login_key_required ?? false,
            
            // Add other categories...
            'multi_currency_enabled' => $setting->multi_currency_enabled ?? false,
            'recurring_billing_enabled' => $setting->recurring_billing_enabled ?? true,
            'time_tracking_enabled' => $setting->time_tracking_enabled ?? true,
            'customer_satisfaction_enabled' => $setting->customer_satisfaction_enabled ?? false,
            'client_portal_enable' => $setting->client_portal_enable ?? true,
            'auto_create_tickets_from_alerts' => $setting->auto_create_tickets_from_alerts ?? false,
            
            // Compliance
            'soc2_compliance_enabled' => $setting->soc2_compliance_enabled ?? false,
            'hipaa_compliance_enabled' => $setting->hipaa_compliance_enabled ?? false,
            'pci_compliance_enabled' => $setting->pci_compliance_enabled ?? false,
            'gdpr_compliance_enabled' => $setting->gdpr_compliance_enabled ?? false,
            
            // System
            'enable_cron' => $setting->enable_cron ?? false,
            'enable_alert_domain_expire' => $setting->enable_alert_domain_expire ?? true,
            'telemetry' => $setting->telemetry ?? false,
            'destructive_deletes_enable' => $setting->destructive_deletes_enable ?? false,
            'module_enable_itdoc' => $setting->module_enable_itdoc ?? true,
            'module_enable_accounting' => $setting->module_enable_accounting ?? true,
            'module_enable_ticketing' => $setting->module_enable_ticketing ?? true,
        ];
    }
    
    /**
     * Get default settings
     * 
     * @return array
     */
    private function getDefaultSettings(): array
    {
        return [
            'company_country' => 'US',
            'company_language' => 'en',
            'company_currency' => 'USD',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'theme' => 'blue',
            'start_page' => 'dashboard',
            'password_min_length' => 8,
            'password_require_special' => true,
            'password_require_numbers' => true,
            'password_require_uppercase' => true,
            'password_expiry_days' => 90,
            'password_history_count' => 5,
            'two_factor_enabled' => false,
            'session_timeout_minutes' => 480,
            'force_single_session' => false,
            'max_login_attempts' => 5,
            'lockout_duration_minutes' => 15,
            'geo_blocking_enabled' => false,
            'audit_logging_enabled' => true,
            'audit_retention_days' => 365,
            'login_key_required' => false,
            'multi_currency_enabled' => false,
            'recurring_billing_enabled' => true,
            'time_tracking_enabled' => true,
            'customer_satisfaction_enabled' => false,
            'client_portal_enable' => true,
            'auto_create_tickets_from_alerts' => false,
            'soc2_compliance_enabled' => false,
            'hipaa_compliance_enabled' => false,
            'pci_compliance_enabled' => false,
            'gdpr_compliance_enabled' => false,
            'enable_cron' => false,
            'enable_alert_domain_expire' => true,
            'telemetry' => false,
            'destructive_deletes_enable' => false,
            'module_enable_itdoc' => true,
            'module_enable_accounting' => true,
            'module_enable_ticketing' => true,
        ];
    }
    
    /**
     * Get or create company customization
     * 
     * @param Company $company
     * @return CompanyCustomization
     */
    public function getCompanyCustomization(Company $company): CompanyCustomization
    {
        return Cache::remember(
            "company_customization_{$company->id}",
            now()->addHours(1),
            function () use ($company) {
                return $company->customization()->firstOrCreate([
                    'company_id' => $company->id,
                ], [
                    'customizations' => [
                        'colors' => CompanyCustomization::DEFAULT_COLORS,
                    ],
                ]);
            }
        );
    }
    
    /**
     * Update company colors
     * 
     * @param Company $company
     * @param array $colors
     * @return bool
     */
    public function updateCompanyColors(Company $company, array $colors): bool
    {
        try {
            DB::beginTransaction();
            
            $customization = $this->getCompanyCustomization($company);
            $customization->setColors($colors);
            $customization->save();
            
            // Clear cache
            Cache::forget("company_customization_{$company->id}");
            Cache::forget("company_css_{$company->id}");
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update company colors: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Apply color preset to company
     * 
     * @param Company $company
     * @param string $preset
     * @return bool
     */
    public function applyColorPreset(Company $company, string $preset): bool
    {
        try {
            DB::beginTransaction();
            
            $customization = $this->getCompanyCustomization($company);
            $customization->applyColorPreset($preset);
            $customization->save();
            
            // Clear cache
            Cache::forget("company_customization_{$company->id}");
            Cache::forget("company_css_{$company->id}");
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to apply color preset: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get company colors
     * 
     * @param Company $company
     * @return array
     */
    public function getCompanyColors(Company $company): array
    {
        $customization = $this->getCompanyCustomization($company);
        return $customization->getColors();
    }
    
    /**
     * Generate CSS custom properties for company
     * 
     * @param Company $company
     * @return string
     */
    public function generateCompanyCss(Company $company): string
    {
        return Cache::remember(
            "company_css_{$company->id}",
            now()->addHours(24),
            function () use ($company) {
                $customization = $this->getCompanyCustomization($company);
                return ":root {\n  " . $customization->getCssCustomProperties() . "\n}";
            }
        );
    }
    
    /**
     * Reset company colors to default
     * 
     * @param Company $company
     * @return bool
     */
    public function resetCompanyColors(Company $company): bool
    {
        try {
            DB::beginTransaction();
            
            $customization = $this->getCompanyCustomization($company);
            $customization->resetColors();
            $customization->save();
            
            // Clear cache
            Cache::forget("company_customization_{$company->id}");
            Cache::forget("company_css_{$company->id}");
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reset company colors: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get available color presets
     * 
     * @return array
     */
    public function getColorPresets(): array
    {
        return CompanyCustomization::getColorPresets();
    }
}