<?php

namespace App\Domains\Core\Services\Settings;

use App\Models\SettingsConfiguration;
use Illuminate\Support\Facades\Crypt;

class FinancialSettingsService extends BaseSettingsService
{
    protected string $domain = SettingsConfiguration::DOMAIN_FINANCIAL;

    /**
     * Get validation rules for each category
     */
    protected function getValidationRules(string $category): array
    {
        switch ($category) {
            case 'billing':
                return [
                    'billing_cycle' => 'required|in:monthly,quarterly,semi-annually,annually',
                    'invoice_prefix' => 'nullable|string|max:10',
                    'invoice_starting_number' => 'nullable|integer|min:1',
                    'payment_terms' => 'required|integer|between:0,365',
                    'late_fee_enabled' => 'boolean',
                    'late_fee_type' => 'required_if:late_fee_enabled,true|in:fixed,percentage',
                    'late_fee_amount' => 'required_if:late_fee_enabled,true|numeric|min:0',
                    'auto_charge_enabled' => 'boolean',
                    'send_invoice_automatically' => 'boolean',
                    'send_payment_reminders' => 'boolean',
                    'reminder_days_before' => 'nullable|array',
                    'reminder_days_after' => 'nullable|array',
                ];

            case 'invoicing':
                return [
                    'invoice_footer' => 'nullable|string|max:1000',
                    'invoice_notes' => 'nullable|string|max:1000',
                    'show_tax_id' => 'boolean',
                    'show_payment_instructions' => 'boolean',
                    'payment_instructions' => 'nullable|string|max:1000',
                    'attach_pdf' => 'boolean',
                    'allow_partial_payments' => 'boolean',
                    'minimum_payment_amount' => 'nullable|numeric|min:0',
                    'invoice_template' => 'nullable|string',
                ];

            case 'taxes':
                return [
                    'tax_enabled' => 'boolean',
                    'tax_name' => 'nullable|string|max:50',
                    'tax_rate' => 'nullable|numeric|between:0,100',
                    'tax_number' => 'nullable|string|max:50',
                    'tax_compound' => 'boolean',
                    'tax_on_shipping' => 'boolean',
                    'tax_inclusive' => 'boolean',
                    'voip_tax_enabled' => 'boolean',
                    'voip_tax_api_key' => 'nullable|string',
                ];

            case 'payment_gateways':
                return [
                    'stripe_enabled' => 'boolean',
                    'stripe_publishable_key' => 'nullable|string',
                    'stripe_secret_key' => 'nullable|string',
                    'paypal_enabled' => 'boolean',
                    'paypal_client_id' => 'nullable|string',
                    'paypal_secret' => 'nullable|string',
                    'paypal_mode' => 'nullable|in:sandbox,live',
                    'ach_enabled' => 'boolean',
                    'check_enabled' => 'boolean',
                    'cash_enabled' => 'boolean',
                ];

            default:
                return [];
        }
    }

    /**
     * Process data before saving (encrypt sensitive data)
     */
    protected function processBeforeSave(string $category, array $data): array
    {
        if ($category === 'taxes' && ! empty($data['voip_tax_api_key'])) {
            if (! $this->isEncrypted($data['voip_tax_api_key'])) {
                $data['voip_tax_api_key'] = Crypt::encryptString($data['voip_tax_api_key']);
            }
        }

        if ($category === 'payment_gateways') {
            // Encrypt payment gateway credentials
            $encryptFields = ['stripe_secret_key', 'paypal_secret'];
            foreach ($encryptFields as $field) {
                if (! empty($data[$field]) && ! $this->isEncrypted($data[$field])) {
                    $data[$field] = Crypt::encryptString($data[$field]);
                }
            }
        }

        return $data;
    }

    /**
     * Check if a value is already encrypted
     */
    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get default settings for a category
     */
    public function getDefaultSettings(string $category): array
    {
        switch ($category) {
            case 'billing':
                return [
                    'billing_cycle' => 'monthly',
                    'payment_terms' => 30,
                    'late_fee_enabled' => false,
                    'auto_charge_enabled' => false,
                    'send_invoice_automatically' => true,
                    'send_payment_reminders' => true,
                    'reminder_days_before' => [7, 3, 1],
                    'reminder_days_after' => [1, 7, 14, 30],
                ];

            case 'invoicing':
                return [
                    'show_tax_id' => true,
                    'show_payment_instructions' => true,
                    'attach_pdf' => true,
                    'allow_partial_payments' => false,
                ];

            case 'taxes':
                return [
                    'tax_enabled' => true,
                    'tax_name' => 'Sales Tax',
                    'tax_rate' => 0,
                    'tax_compound' => false,
                    'tax_on_shipping' => false,
                    'tax_inclusive' => false,
                    'voip_tax_enabled' => false,
                ];

            case 'payment_gateways':
                return [
                    'stripe_enabled' => false,
                    'paypal_enabled' => false,
                    'paypal_mode' => 'sandbox',
                    'ach_enabled' => false,
                    'check_enabled' => true,
                    'cash_enabled' => true,
                ];

            default:
                return [];
        }
    }

    /**
     * Get category metadata
     */
    public function getCategoryMetadata(string $category): array
    {
        switch ($category) {
            case 'billing':
                return [
                    'name' => 'Billing Configuration',
                    'description' => 'Configure billing cycles and payment terms',
                    'icon' => 'calculator',
                ];

            case 'invoicing':
                return [
                    'name' => 'Invoicing',
                    'description' => 'Invoice templates and settings',
                    'icon' => 'document-text',
                ];

            case 'taxes':
                return [
                    'name' => 'Tax Settings',
                    'description' => 'Configure tax rates and VoIP taxes',
                    'icon' => 'receipt-percent',
                ];

            case 'payment_gateways':
                return [
                    'name' => 'Payment Gateways',
                    'description' => 'Configure payment processors',
                    'icon' => 'credit-card',
                ];

            default:
                return [];
        }
    }
}
