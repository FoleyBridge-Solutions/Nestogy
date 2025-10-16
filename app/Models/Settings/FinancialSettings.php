<?php

namespace App\Models\Settings;

class FinancialSettings extends SettingCategory
{
    public function getCategory(): string
    {
        return 'financial';
    }

    public function getAttributes(): array
    {
        return [
            'default_transfer_from_account',
            'default_transfer_to_account',
            'default_payment_account',
            'default_expense_account',
            'default_payment_method',
            'default_expense_payment_method',
            'default_calendar',
            'default_net_terms',
            'default_hourly_rate',
            'multi_currency_enabled',
            'supported_currencies',
            'exchange_rate_provider',
            'auto_update_exchange_rates',
            'tax_calculation_settings',
            'tax_engine_provider',
            'payment_gateway_settings',
            'stripe_settings',
            'square_settings',
            'paypal_settings',
            'authorize_net_settings',
            'ach_settings',
            'wire_settings',
            'check_settings',
            'recurring_billing_enabled',
            'recurring_billing_settings',
            'late_fee_settings',
            'collection_settings',
            'accounting_integration_settings',
            'quickbooks_settings',
            'xero_settings',
            'sage_settings',
            'revenue_recognition_enabled',
            'revenue_recognition_settings',
            'purchase_order_settings',
            'expense_approval_settings',
            'invoice_prefix',
            'invoice_next_number',
            'invoice_footer',
            'invoice_from_name',
            'invoice_from_email',
            'invoice_late_fee_enable',
            'invoice_late_fee_percent',
            'quote_prefix',
            'quote_next_number',
            'quote_footer',
            'quote_from_name',
            'quote_from_email',
            'recurring_auto_send_invoice',
            'send_invoice_reminders',
            'invoice_overdue_reminders',
        ];
    }

    public function getDefaultPaymentAccount(): ?int
    {
        return $this->get('default_payment_account');
    }

    public function setDefaultPaymentAccount(?int $accountId): self
    {
        $this->set('default_payment_account', $accountId);
        return $this;
    }

    public function getDefaultExpenseAccount(): ?int
    {
        return $this->get('default_expense_account');
    }

    public function setDefaultExpenseAccount(?int $accountId): self
    {
        $this->set('default_expense_account', $accountId);
        return $this;
    }

    public function getDefaultNetTerms(): int
    {
        return $this->get('default_net_terms', 30);
    }

    public function setDefaultNetTerms(int $days): self
    {
        $this->set('default_net_terms', $days);
        return $this;
    }

    public function getDefaultHourlyRate(): float
    {
        return (float) $this->get('default_hourly_rate', 0);
    }

    public function setDefaultHourlyRate(float $rate): self
    {
        $this->set('default_hourly_rate', $rate);
        return $this;
    }

    public function getFormattedHourlyRate(): string
    {
        return '$' . number_format($this->getDefaultHourlyRate(), 2) . '/hr';
    }

    public function isMultiCurrencyEnabled(): bool
    {
        return (bool) $this->get('multi_currency_enabled', false);
    }

    public function setMultiCurrencyEnabled(bool $enabled): self
    {
        $this->set('multi_currency_enabled', $enabled);
        return $this;
    }

    public function getSupportedCurrencies(): array
    {
        return $this->get('supported_currencies', []);
    }

    public function setSupportedCurrencies(array $currencies): self
    {
        $this->set('supported_currencies', $currencies);
        return $this;
    }

    public function getInvoicePrefix(): ?string
    {
        return $this->get('invoice_prefix', 'INV');
    }

    public function setInvoicePrefix(?string $prefix): self
    {
        $this->set('invoice_prefix', $prefix);
        return $this;
    }

    public function getInvoiceNextNumber(): int
    {
        return $this->get('invoice_next_number', 1);
    }

    public function setInvoiceNextNumber(int $number): self
    {
        $this->set('invoice_next_number', $number);
        return $this;
    }

    public function getNextInvoiceNumber(): int
    {
        $number = $this->getInvoiceNextNumber() ?: 1;
        $this->setInvoiceNextNumber($number + 1);
        $this->model->save();
        return $number;
    }

    public function getInvoiceFooter(): ?string
    {
        return $this->get('invoice_footer');
    }

    public function setInvoiceFooter(?string $footer): self
    {
        $this->set('invoice_footer', $footer);
        return $this;
    }

    public function getInvoiceFromName(): ?string
    {
        return $this->get('invoice_from_name');
    }

    public function setInvoiceFromName(?string $name): self
    {
        $this->set('invoice_from_name', $name);
        return $this;
    }

    public function getInvoiceFromEmail(): ?string
    {
        return $this->get('invoice_from_email');
    }

    public function setInvoiceFromEmail(?string $email): self
    {
        $this->set('invoice_from_email', $email);
        return $this;
    }

    public function isInvoiceLateFeeEnabled(): bool
    {
        return (bool) $this->get('invoice_late_fee_enable', false);
    }

    public function setInvoiceLateFeeEnabled(bool $enabled): self
    {
        $this->set('invoice_late_fee_enable', $enabled);
        return $this;
    }

    public function getInvoiceLateFeePercent(): float
    {
        return (float) $this->get('invoice_late_fee_percent', 0);
    }

    public function setInvoiceLateFeePercent(float $percent): self
    {
        $this->set('invoice_late_fee_percent', $percent);
        return $this;
    }

    public function getQuotePrefix(): ?string
    {
        return $this->get('quote_prefix', 'QTE');
    }

    public function setQuotePrefix(?string $prefix): self
    {
        $this->set('quote_prefix', $prefix);
        return $this;
    }

    public function getQuoteNextNumber(): int
    {
        return $this->get('quote_next_number', 1);
    }

    public function setQuoteNextNumber(int $number): self
    {
        $this->set('quote_next_number', $number);
        return $this;
    }

    public function getNextQuoteNumber(): int
    {
        $number = $this->getQuoteNextNumber() ?: 1;
        $this->setQuoteNextNumber($number + 1);
        $this->model->save();
        return $number;
    }

    public function isRecurringBillingEnabled(): bool
    {
        return (bool) $this->get('recurring_billing_enabled', false);
    }

    public function setRecurringBillingEnabled(bool $enabled): self
    {
        $this->set('recurring_billing_enabled', $enabled);
        return $this;
    }

    public function getStripeSettings(): ?array
    {
        return $this->get('stripe_settings');
    }

    public function setStripeSettings(?array $settings): self
    {
        $this->set('stripe_settings', $settings);
        return $this;
    }

    public function isStripeEnabled(): bool
    {
        $settings = $this->getStripeSettings() ?? [];
        return $settings['enabled'] ?? false;
    }

    public function getStripePublishableKey(): ?string
    {
        $settings = $this->getStripeSettings() ?? [];
        return $settings['publishable_key'] ?? null;
    }

    public function getPaypalSettings(): ?array
    {
        return $this->get('paypal_settings');
    }

    public function setPaypalSettings(?array $settings): self
    {
        $this->set('paypal_settings', $settings);
        return $this;
    }

    public function isPaypalEnabled(): bool
    {
        $settings = $this->getPaypalSettings() ?? [];
        return $settings['enabled'] ?? false;
    }

    public function getPaypalClientId(): ?string
    {
        $settings = $this->getPaypalSettings() ?? [];
        return $settings['client_id'] ?? null;
    }

    public function getSquareSettings(): ?array
    {
        return $this->get('square_settings');
    }

    public function setSquareSettings(?array $settings): self
    {
        $this->set('square_settings', $settings);
        return $this;
    }

    public function isSquareEnabled(): bool
    {
        $settings = $this->getSquareSettings() ?? [];
        return $settings['enabled'] ?? false;
    }

    public function getAuthorizeNetSettings(): ?array
    {
        return $this->get('authorize_net_settings');
    }

    public function setAuthorizeNetSettings(?array $settings): self
    {
        $this->set('authorize_net_settings', $settings);
        return $this;
    }

    public function isAuthorizeNetEnabled(): bool
    {
        $settings = $this->getAuthorizeNetSettings() ?? [];
        return $settings['enabled'] ?? false;
    }

    public function getAchSettings(): ?array
    {
        return $this->get('ach_settings');
    }

    public function setAchSettings(?array $settings): self
    {
        $this->set('ach_settings', $settings);
        return $this;
    }

    public function isAchEnabled(): bool
    {
        $settings = $this->getAchSettings() ?? [];
        return $settings['enabled'] ?? false;
    }

    public function getAchBankName(): ?string
    {
        $settings = $this->getAchSettings() ?? [];
        return $settings['bank_name'] ?? null;
    }

    public function getAchRoutingNumber(): ?string
    {
        $settings = $this->getAchSettings() ?? [];
        return $settings['routing_number'] ?? null;
    }

    public function getWireSettings(): ?array
    {
        return $this->get('wire_settings');
    }

    public function setWireSettings(?array $settings): self
    {
        $this->set('wire_settings', $settings);
        return $this;
    }

    public function isWireEnabled(): bool
    {
        $settings = $this->getWireSettings() ?? [];
        return $settings['enabled'] ?? false;
    }

    public function getWireBankName(): ?string
    {
        $settings = $this->getWireSettings() ?? [];
        return $settings['bank_name'] ?? null;
    }

    public function getCheckSettings(): ?array
    {
        return $this->get('check_settings');
    }

    public function setCheckSettings(?array $settings): self
    {
        $this->set('check_settings', $settings);
        return $this;
    }

    public function isCheckEnabled(): bool
    {
        $settings = $this->getCheckSettings() ?? [];
        return $settings['enabled'] ?? false;
    }

    public function getCheckPayToName(): ?string
    {
        $settings = $this->getCheckSettings() ?? [];
        return $settings['payto_name'] ?? null;
    }

    public function getQuickbooksSettings(): ?array
    {
        return $this->get('quickbooks_settings');
    }

    public function setQuickbooksSettings(?array $settings): self
    {
        $this->set('quickbooks_settings', $settings);
        return $this;
    }

    public function isQuickbooksEnabled(): bool
    {
        $settings = $this->getQuickbooksSettings() ?? [];
        return $settings['enabled'] ?? false;
    }

    public function getXeroSettings(): ?array
    {
        return $this->get('xero_settings');
    }

    public function setXeroSettings(?array $settings): self
    {
        $this->set('xero_settings', $settings);
        return $this;
    }

    public function isXeroEnabled(): bool
    {
        $settings = $this->getXeroSettings() ?? [];
        return $settings['enabled'] ?? false;
    }
}
