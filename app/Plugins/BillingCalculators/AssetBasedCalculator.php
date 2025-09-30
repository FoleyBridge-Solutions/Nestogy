<?php

namespace App\Plugins\BillingCalculators;

use App\Contracts\BillingCalculatorInterface;
use App\Domains\Contract\Models\Contract;
use Money\Currency;
use Money\Money;

/**
 * Asset-based billing calculator
 * Calculates billing based on number of assets
 */
class AssetBasedCalculator implements BillingCalculatorInterface
{
    protected array $config = [];

    public function getName(): string
    {
        return 'Asset-Based Calculator';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Calculates contract billing based on the number of assets covered by the contract';
    }

    public function getAuthor(): string
    {
        return 'Nestogy Core Team';
    }

    public function getConfigurationSchema(): array
    {
        return [
            'base_price' => [
                'type' => 'number',
                'required' => true,
                'description' => 'Base price per asset',
                'minimum' => 0,
            ],
            'tier_pricing' => [
                'type' => 'array',
                'description' => 'Tiered pricing configuration',
                'items' => [
                    'min_assets' => ['type' => 'integer', 'minimum' => 0],
                    'max_assets' => ['type' => 'integer', 'minimum' => 0],
                    'price_per_asset' => ['type' => 'number', 'minimum' => 0],
                ],
            ],
            'asset_type_multipliers' => [
                'type' => 'object',
                'description' => 'Price multipliers per asset type',
                'properties' => [
                    'server' => ['type' => 'number', 'default' => 1.5],
                    'workstation' => ['type' => 'number', 'default' => 1.0],
                    'network_device' => ['type' => 'number', 'default' => 2.0],
                    'mobile_device' => ['type' => 'number', 'default' => 0.5],
                ],
            ],
            'minimum_charge' => [
                'type' => 'number',
                'description' => 'Minimum monthly charge regardless of asset count',
                'default' => 0,
            ],
            'maximum_charge' => [
                'type' => 'number',
                'description' => 'Maximum monthly charge cap',
                'default' => null,
            ],
        ];
    }

    public function validateConfiguration(array $config): array
    {
        $errors = [];

        if (! isset($config['base_price']) || $config['base_price'] < 0) {
            $errors[] = 'Base price must be a positive number';
        }

        if (isset($config['tier_pricing'])) {
            foreach ($config['tier_pricing'] as $index => $tier) {
                if (! isset($tier['min_assets']) || ! isset($tier['max_assets']) || ! isset($tier['price_per_asset'])) {
                    $errors[] = "Tier pricing entry {$index} is missing required fields";
                }

                if ($tier['min_assets'] > $tier['max_assets']) {
                    $errors[] = "Tier pricing entry {$index}: min_assets cannot be greater than max_assets";
                }
            }
        }

        return $errors;
    }

    public function initialize(array $config = []): void
    {
        $this->config = array_merge([
            'base_price' => 10.00,
            'tier_pricing' => [],
            'asset_type_multipliers' => [
                'server' => 1.5,
                'workstation' => 1.0,
                'network_device' => 2.0,
                'mobile_device' => 0.5,
            ],
            'minimum_charge' => 0,
            'maximum_charge' => null,
        ], $config);
    }

    public function isCompatible(): bool
    {
        return true; // Compatible with all system versions
    }

    public function getRequiredPermissions(): array
    {
        return ['view-assets', 'calculate-billing'];
    }

    public function getDependencies(): array
    {
        return ['App\Models\Asset'];
    }

    public function calculateAmount(Contract $contract, array $context = []): Money
    {
        $monthlyAmount = $this->calculateMonthlyRecurring($contract, $context);
        $oneTimeAmount = $this->calculateOneTime($contract, $context);

        return $monthlyAmount->add($oneTimeAmount);
    }

    public function calculateMonthlyRecurring(Contract $contract, array $context = []): Money
    {
        $currency = new Currency($contract->currency_code ?? 'USD');
        $assets = $this->getContractAssets($contract, $context);
        $assetCount = $assets->count();

        if ($assetCount === 0) {
            return new Money($this->config['minimum_charge'] * 100, $currency);
        }

        $amount = $this->calculateTieredAmount($assetCount);

        // Apply asset type multipliers
        if (! empty($this->config['asset_type_multipliers'])) {
            $amount = $this->applyAssetTypeMultipliers($assets, $amount);
        }

        // Apply minimum charge
        if ($this->config['minimum_charge'] > 0 && $amount < $this->config['minimum_charge']) {
            $amount = $this->config['minimum_charge'];
        }

        // Apply maximum charge cap
        if ($this->config['maximum_charge'] && $amount > $this->config['maximum_charge']) {
            $amount = $this->config['maximum_charge'];
        }

        return new Money($amount * 100, $currency);
    }

    public function calculateOneTime(Contract $contract, array $context = []): Money
    {
        $currency = new Currency($contract->currency_code ?? 'USD');

        // One-time setup fees based on asset count
        $assets = $this->getContractAssets($contract, $context);
        $setupFee = $assets->count() * ($this->config['setup_fee_per_asset'] ?? 0);

        return new Money($setupFee * 100, $currency);
    }

    public function calculateProrated(Contract $contract, \DateTime $startDate, \DateTime $endDate, array $context = []): Money
    {
        $monthlyAmount = $this->calculateMonthlyRecurring($contract, $context);

        $totalDays = $startDate->diff($endDate)->days + 1;
        $daysInMonth = $startDate->format('t');

        $proratedAmount = $monthlyAmount->multiply($totalDays / $daysInMonth);

        return $proratedAmount;
    }

    public function getCalculationBreakdown(Contract $contract, array $context = []): array
    {
        $assets = $this->getContractAssets($contract, $context);
        $assetCount = $assets->count();
        $baseAmount = $this->calculateTieredAmount($assetCount);

        $breakdown = [
            'asset_count' => $assetCount,
            'base_calculation' => [
                'method' => $this->getTierMethod($assetCount),
                'amount' => $baseAmount,
            ],
            'asset_types' => [],
            'adjustments' => [],
            'final_amount' => $baseAmount,
        ];

        // Asset type breakdown
        $assetsByType = $assets->groupBy('type');
        foreach ($assetsByType as $type => $typeAssets) {
            $multiplier = $this->config['asset_type_multipliers'][$type] ?? 1.0;
            $breakdown['asset_types'][$type] = [
                'count' => $typeAssets->count(),
                'multiplier' => $multiplier,
                'contribution' => $typeAssets->count() * $this->config['base_price'] * $multiplier,
            ];
        }

        // Minimum charge adjustment
        if ($this->config['minimum_charge'] > 0 && $baseAmount < $this->config['minimum_charge']) {
            $breakdown['adjustments']['minimum_charge'] = [
                'applied' => true,
                'amount' => $this->config['minimum_charge'] - $baseAmount,
            ];
            $breakdown['final_amount'] = $this->config['minimum_charge'];
        }

        // Maximum charge cap
        if ($this->config['maximum_charge'] && $baseAmount > $this->config['maximum_charge']) {
            $breakdown['adjustments']['maximum_cap'] = [
                'applied' => true,
                'amount' => $this->config['maximum_charge'] - $baseAmount,
            ];
            $breakdown['final_amount'] = $this->config['maximum_charge'];
        }

        return $breakdown;
    }

    public function validateContractData(Contract $contract): array
    {
        $errors = [];

        if (! $contract->client_id) {
            $errors[] = 'Contract must have a client assigned';
        }

        if (! $contract->currency_code) {
            $errors[] = 'Contract must have a currency code';
        }

        return $errors;
    }

    public function getSupportedModels(): array
    {
        return ['per_asset', 'tiered_asset', 'asset_type_based'];
    }

    public function getRequiredFields(): array
    {
        return ['client_id', 'currency_code'];
    }

    public function previewCalculation(array $contractData, array $context = []): array
    {
        // Mock contract for preview
        $contract = new Contract($contractData);

        return [
            'monthly_recurring' => $this->calculateMonthlyRecurring($contract, $context)->getAmount() / 100,
            'one_time' => $this->calculateOneTime($contract, $context)->getAmount() / 100,
            'breakdown' => $this->getCalculationBreakdown($contract, $context),
        ];
    }

    public function getCalculationParameters(): array
    {
        return [
            'asset_count' => [
                'label' => 'Number of Assets',
                'type' => 'integer',
                'description' => 'Total number of assets covered by the contract',
            ],
            'asset_types' => [
                'label' => 'Asset Types',
                'type' => 'array',
                'description' => 'Types of assets and their quantities',
            ],
        ];
    }

    public function applyAdjustments(Money $baseAmount, array $adjustments, Contract $contract): Money
    {
        $adjustedAmount = $baseAmount;

        foreach ($adjustments as $adjustment) {
            switch ($adjustment['type']) {
                case 'discount':
                    if ($adjustment['is_percentage']) {
                        $adjustedAmount = $adjustedAmount->multiply(1 - ($adjustment['value'] / 100));
                    } else {
                        $currency = new Currency($contract->currency_code ?? 'USD');
                        $adjustedAmount = $adjustedAmount->subtract(new Money($adjustment['value'] * 100, $currency));
                    }
                    break;

                case 'surcharge':
                    if ($adjustment['is_percentage']) {
                        $adjustedAmount = $adjustedAmount->multiply(1 + ($adjustment['value'] / 100));
                    } else {
                        $currency = new Currency($contract->currency_code ?? 'USD');
                        $adjustedAmount = $adjustedAmount->add(new Money($adjustment['value'] * 100, $currency));
                    }
                    break;
            }
        }

        return $adjustedAmount;
    }

    public function calculateTaxes(Money $amount, Contract $contract, array $context = []): Money
    {
        // Integrate with tax service if available
        $taxRate = $context['tax_rate'] ?? 0;

        return $amount->multiply(1 + ($taxRate / 100));
    }

    public function getBillingFrequencies(): array
    {
        return ['monthly', 'quarterly', 'annually'];
    }

    public function supportsAutomaticBilling(): bool
    {
        return true;
    }

    public function getNextBillingDate(Contract $contract): ?\DateTime
    {
        if (! $contract->start_date) {
            return null;
        }

        $frequency = $contract->billing_frequency ?? 'monthly';
        $nextBilling = clone $contract->start_date;

        switch ($frequency) {
            case 'monthly':
                $nextBilling->modify('+1 month');
                break;
            case 'quarterly':
                $nextBilling->modify('+3 months');
                break;
            case 'annually':
                $nextBilling->modify('+1 year');
                break;
        }

        return $nextBilling;
    }

    /**
     * Get contract assets
     */
    protected function getContractAssets(Contract $contract, array $context = [])
    {
        if (isset($context['assets'])) {
            return collect($context['assets']);
        }

        return $contract->supportedAssets ?? collect();
    }

    /**
     * Calculate tiered amount based on asset count
     */
    protected function calculateTieredAmount(int $assetCount): float
    {
        if (empty($this->config['tier_pricing'])) {
            return $assetCount * $this->config['base_price'];
        }

        $totalAmount = 0;

        foreach ($this->config['tier_pricing'] as $tier) {
            $tierMin = $tier['min_assets'];
            $tierMax = $tier['max_assets'];
            $tierPrice = $tier['price_per_asset'];

            if ($assetCount > $tierMin) {
                $assetsInTier = min($assetCount, $tierMax) - $tierMin;
                $totalAmount += $assetsInTier * $tierPrice;
            }
        }

        return $totalAmount;
    }

    /**
     * Apply asset type multipliers
     */
    protected function applyAssetTypeMultipliers($assets, float $baseAmount): float
    {
        $adjustedAmount = 0;
        $assetsByType = $assets->groupBy('type');

        foreach ($assetsByType as $type => $typeAssets) {
            $multiplier = $this->config['asset_type_multipliers'][$type] ?? 1.0;
            $typeAmount = $typeAssets->count() * $this->config['base_price'] * $multiplier;
            $adjustedAmount += $typeAmount;
        }

        return $adjustedAmount;
    }

    /**
     * Get tier method description
     */
    protected function getTierMethod(int $assetCount): string
    {
        if (empty($this->config['tier_pricing'])) {
            return "Flat rate: {$assetCount} assets × \${$this->config['base_price']}";
        }

        return "Tiered pricing applied for {$assetCount} assets";
    }
}
