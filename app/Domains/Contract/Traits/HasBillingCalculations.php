<?php

namespace App\Domains\Contract\Traits;

use App\Domains\Contract\Services\ContractConfigurationRegistry;

/**
 * HasBillingCalculations Trait
 * 
 * Provides common billing calculation methods for contract templates.
 * Eliminates duplication of billing calculation logic.
 */
trait HasBillingCalculations
{
    /**
     * Check if billing model supports specific feature
     */
    protected function supportsBillingFeature(string $feature): bool
    {
        $companyId = $this->resolveCompanyId();
        $registry = new ContractConfigurationRegistry($companyId);
        $billingModels = $registry->getBillingModels();
        $supportedKeys = [];
        
        foreach ($billingModels as $key => $name) {
            if (str_contains(strtolower($name), strtolower($feature)) || 
                str_contains(strtolower($name), 'hybrid')) {
                $supportedKeys[] = $key;
            }
        }
        
        return in_array($this->billing_model, $supportedKeys);
    }

    /**
     * Calculate charges for a billing type
     */
    protected function calculateChargesForType(array $items, array $rules, ?float $defaultRate = null): float
    {
        $total = 0;

        foreach ($items as $type => $count) {
            $rate = $rules[$type]['rate'] ?? $defaultRate ?? 0;
            $total += $rate * $count;
        }

        return $total;
    }

    /**
     * Get billing rate for specific type
     */
    protected function getBillingRate(string $type, array $rules, ?float $defaultRate = null): ?float
    {
        return $rules[$type]['rate'] ?? $defaultRate;
    }

    /**
     * Calculate asset-based charges
     */
    protected function calculateAssetCharges(array $assets): float
    {
        return $this->calculateChargesForType(
            $assets, 
            $this->asset_billing_rules ?? [], 
            $this->default_per_asset_rate
        );
    }

    /**
     * Calculate contact-based charges
     */
    protected function calculateContactCharges(array $contacts): float
    {
        return $this->calculateChargesForType(
            $contacts, 
            $this->contact_billing_rules ?? [], 
            $this->default_per_contact_rate
        );
    }

    /**
     * Check if template supports asset-based billing
     */
    public function supportsAssetBilling(): bool
    {
        return $this->supportsBillingFeature('asset');
    }

    /**
     * Check if template supports contact-based billing
     */
    public function supportsContactBilling(): bool
    {
        return $this->supportsBillingFeature('contact') || $this->supportsBillingFeature('user');
    }

    /**
     * Check if template supports tiered billing
     */
    public function supportsTieredBilling(): bool
    {
        return $this->supportsBillingFeature('tiered');
    }

    /**
     * Get billing rate for asset type
     */
    public function getAssetBillingRate(string $assetType): ?float
    {
        return $this->getBillingRate($assetType, $this->asset_billing_rules ?? [], $this->default_per_asset_rate);
    }

    /**
     * Get billing rate for contact tier
     */
    public function getContactBillingRate(string $tier): ?float
    {
        return $this->getBillingRate($tier, $this->contact_billing_rules ?? [], $this->default_per_contact_rate);
    }
}