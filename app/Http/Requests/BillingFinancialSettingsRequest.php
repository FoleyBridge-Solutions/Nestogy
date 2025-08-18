<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BillingFinancialSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Currency and Exchange Rates
            'multi_currency_enabled' => 'boolean',
            'supported_currencies' => 'nullable|array',
            'supported_currencies.*' => 'string|size:3',
            'exchange_rate_provider' => 'nullable|string|in:fixer,openexchangerates,currencylayer,exchangerate-api',
            'auto_update_exchange_rates' => 'boolean',
            
            // Tax Configuration
            'tax_calculation_settings' => 'nullable|array',
            'tax_calculation_settings.enabled' => 'boolean',
            'tax_calculation_settings.default_rate' => 'nullable|numeric|min:0|max:100',
            'tax_calculation_settings.compound_tax' => 'boolean',
            'tax_calculation_settings.tax_inclusive' => 'boolean',
            'tax_engine_provider' => 'nullable|string|in:avalara,taxjar,taxcloud,manual',
            
            // Payment Gateway Settings
            'payment_gateway_settings' => 'nullable|array',
            'payment_gateway_settings.default_gateway' => 'nullable|string|in:stripe,square,paypal,authorize_net,ach',
            'payment_gateway_settings.auto_charge' => 'boolean',
            'payment_gateway_settings.save_cards' => 'boolean',
            'payment_gateway_settings.require_cvv' => 'boolean',
            
            // Stripe Settings
            'stripe_settings' => 'nullable|array',
            'stripe_settings.enabled' => 'boolean',
            'stripe_settings.public_key' => 'nullable|string|max:255',
            'stripe_settings.secret_key' => 'nullable|string|max:255',
            'stripe_settings.webhook_secret' => 'nullable|string|max:255',
            'stripe_settings.connect_enabled' => 'boolean',
            
            // Square Settings
            'square_settings' => 'nullable|array',
            'square_settings.enabled' => 'boolean',
            'square_settings.application_id' => 'nullable|string|max:255',
            'square_settings.access_token' => 'nullable|string|max:255',
            'square_settings.location_id' => 'nullable|string|max:255',
            'square_settings.sandbox_mode' => 'boolean',
            
            // PayPal Settings
            'paypal_settings' => 'nullable|array',
            'paypal_settings.enabled' => 'boolean',
            'paypal_settings.client_id' => 'nullable|string|max:255',
            'paypal_settings.client_secret' => 'nullable|string|max:255',
            'paypal_settings.sandbox_mode' => 'boolean',
            
            // Authorize.Net Settings
            'authorize_net_settings' => 'nullable|array',
            'authorize_net_settings.enabled' => 'boolean',
            'authorize_net_settings.api_login_id' => 'nullable|string|max:255',
            'authorize_net_settings.transaction_key' => 'nullable|string|max:255',
            'authorize_net_settings.sandbox_mode' => 'boolean',
            
            // ACH Settings
            'ach_settings' => 'nullable|array',
            'ach_settings.enabled' => 'boolean',
            'ach_settings.provider' => 'nullable|string|in:plaid,dwolla,stripe',
            'ach_settings.verification_required' => 'boolean',
            'ach_settings.same_day_ach' => 'boolean',
            
            // Recurring Billing
            'recurring_billing_enabled' => 'boolean',
            'recurring_billing_settings' => 'nullable|array',
            'recurring_billing_settings.retry_attempts' => 'integer|min:0|max:5',
            'recurring_billing_settings.retry_interval_days' => 'integer|min:1|max:30',
            'recurring_billing_settings.grace_period_days' => 'integer|min:0|max:30',
            'recurring_billing_settings.auto_suspend' => 'boolean',
            'recurring_billing_settings.dunning_emails' => 'boolean',
            
            // Late Fee Settings
            'late_fee_settings' => 'nullable|array',
            'late_fee_settings.enabled' => 'boolean',
            'late_fee_settings.type' => 'nullable|string|in:percentage,fixed',
            'late_fee_settings.percentage' => 'nullable|numeric|min:0|max:100',
            'late_fee_settings.fixed_amount' => 'nullable|numeric|min:0',
            'late_fee_settings.grace_period_days' => 'integer|min:0|max:30',
            'late_fee_settings.max_fees' => 'integer|min:0|max:10',
            
            // Collection Settings
            'collection_settings' => 'nullable|array',
            'collection_settings.enabled' => 'boolean',
            'collection_settings.first_notice_days' => 'integer|min:1|max:30',
            'collection_settings.second_notice_days' => 'integer|min:1|max:60',
            'collection_settings.final_notice_days' => 'integer|min:1|max:90',
            'collection_settings.auto_suspend_days' => 'integer|min:1|max:120',
            'collection_settings.write_off_days' => 'integer|min:30|max:365',
            
            // Accounting Integration
            'accounting_integration_settings' => 'nullable|array',
            'accounting_integration_settings.enabled' => 'boolean',
            'accounting_integration_settings.provider' => 'nullable|string|in:quickbooks,xero,sage,netsuite',
            'accounting_integration_settings.sync_frequency' => 'nullable|string|in:real_time,hourly,daily,weekly',
            'accounting_integration_settings.auto_sync' => 'boolean',
            
            // QuickBooks Settings
            'quickbooks_settings' => 'nullable|array',
            'quickbooks_settings.enabled' => 'boolean',
            'quickbooks_settings.company_id' => 'nullable|string|max:255',
            'quickbooks_settings.client_id' => 'nullable|string|max:255',
            'quickbooks_settings.client_secret' => 'nullable|string|max:255',
            'quickbooks_settings.access_token' => 'nullable|string|max:1000',
            'quickbooks_settings.refresh_token' => 'nullable|string|max:1000',
            'quickbooks_settings.sandbox_mode' => 'boolean',
            
            // Xero Settings
            'xero_settings' => 'nullable|array',
            'xero_settings.enabled' => 'boolean',
            'xero_settings.client_id' => 'nullable|string|max:255',
            'xero_settings.client_secret' => 'nullable|string|max:255',
            'xero_settings.tenant_id' => 'nullable|string|max:255',
            'xero_settings.access_token' => 'nullable|string|max:1000',
            'xero_settings.refresh_token' => 'nullable|string|max:1000',
            
            // Sage Settings
            'sage_settings' => 'nullable|array',
            'sage_settings.enabled' => 'boolean',
            'sage_settings.server_url' => 'nullable|url|max:255',
            'sage_settings.database_name' => 'nullable|string|max:255',
            'sage_settings.username' => 'nullable|string|max:255',
            'sage_settings.password' => 'nullable|string|max:255',
            
            // Revenue Recognition
            'revenue_recognition_enabled' => 'boolean',
            'revenue_recognition_settings' => 'nullable|array',
            'revenue_recognition_settings.method' => 'nullable|string|in:cash,accrual,contract',
            'revenue_recognition_settings.recognition_point' => 'nullable|string|in:invoice_date,payment_date,delivery_date',
            'revenue_recognition_settings.deferred_revenue' => 'boolean',
            
            // Purchase Orders
            'purchase_order_settings' => 'nullable|array',
            'purchase_order_settings.enabled' => 'boolean',
            'purchase_order_settings.approval_required' => 'boolean',
            'purchase_order_settings.approval_threshold' => 'nullable|numeric|min:0',
            'purchase_order_settings.auto_numbering' => 'boolean',
            'purchase_order_settings.number_prefix' => 'nullable|string|max:10',
            'purchase_order_settings.next_number' => 'nullable|integer|min:1',
            
            // Expense Approval
            'expense_approval_settings' => 'nullable|array',
            'expense_approval_settings.enabled' => 'boolean',
            'expense_approval_settings.approval_threshold' => 'nullable|numeric|min:0',
            'expense_approval_settings.require_receipts' => 'boolean',
            'expense_approval_settings.auto_approve_under_threshold' => 'boolean',
            'expense_approval_settings.approval_workflow' => 'nullable|array',
            
            // Invoice Settings
            'invoice_prefix' => 'nullable|string|max:10',
            'invoice_next_number' => 'nullable|integer|min:1',
            'invoice_footer' => 'nullable|string|max:1000',
            'invoice_from_name' => 'nullable|string|max:255',
            'invoice_from_email' => 'nullable|email|max:255',
            'invoice_late_fee_enable' => 'boolean',
            'invoice_late_fee_percent' => 'nullable|numeric|min:0|max:100',
            
            // Quote Settings
            'quote_prefix' => 'nullable|string|max:10',
            'quote_next_number' => 'nullable|integer|min:1',
            'quote_footer' => 'nullable|string|max:1000',
            'quote_from_name' => 'nullable|string|max:255',
            'quote_from_email' => 'nullable|email|max:255',
            
            // Default Settings
            'default_transfer_from_account' => 'nullable|integer|exists:accounts,id',
            'default_transfer_to_account' => 'nullable|integer|exists:accounts,id',
            'default_payment_account' => 'nullable|integer|exists:accounts,id',
            'default_expense_account' => 'nullable|integer|exists:accounts,id',
            'default_payment_method' => 'nullable|string|max:50',
            'default_expense_payment_method' => 'nullable|string|max:50',
            'default_net_terms' => 'nullable|integer|min:0|max:365',
            'default_hourly_rate' => 'nullable|numeric|min:0|max:9999.99',
            'profitability_tracking_settings' => 'nullable|array',
            'profitability_tracking_settings.goal_margin' => 'nullable|numeric|min:0|max:100',
            
            // Invoice Reminders
            'recurring_auto_send_invoice' => 'boolean',
            'send_invoice_reminders' => 'boolean',
            'invoice_overdue_reminders' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'supported_currencies.*.size' => 'Each currency code must be exactly 3 characters.',
            'tax_calculation_settings.default_rate.max' => 'Tax rate cannot exceed 100%.',
            'stripe_settings.public_key.required_if' => 'Stripe public key is required when Stripe is enabled.',
            'stripe_settings.secret_key.required_if' => 'Stripe secret key is required when Stripe is enabled.',
            'late_fee_settings.percentage.max' => 'Late fee percentage cannot exceed 100%.',
            'default_hourly_rate.max' => 'Hourly rate cannot exceed $9,999.99.',
            'invoice_from_email.email' => 'Invoice from email must be a valid email address.',
            'quote_from_email.email' => 'Quote from email must be a valid email address.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'multi_currency_enabled' => 'multi-currency support',
            'auto_update_exchange_rates' => 'auto-update exchange rates',
            'tax_engine_provider' => 'tax engine provider',
            'recurring_billing_enabled' => 'recurring billing',
            'revenue_recognition_enabled' => 'revenue recognition',
            'invoice_late_fee_enable' => 'late fees',
            'default_net_terms' => 'default payment terms',
            'default_hourly_rate' => 'default hourly rate',
            'goal_margin' => 'goal margin',
        ];
    }
}