<?php

namespace App\Contracts;

use App\Domains\Contract\Models\Contract;
use Money\Money;

/**
 * Interface for billing calculator plugins
 */
interface BillingCalculatorInterface extends ContractPluginInterface
{
    /**
     * Calculate contract billing amount
     */
    public function calculateAmount(Contract $contract, array $context = []): Money;

    /**
     * Calculate recurring monthly amount
     */
    public function calculateMonthlyRecurring(Contract $contract, array $context = []): Money;

    /**
     * Calculate one-time amounts
     */
    public function calculateOneTime(Contract $contract, array $context = []): Money;

    /**
     * Calculate prorated amount for partial periods
     */
    public function calculateProrated(Contract $contract, \DateTime $startDate, \DateTime $endDate, array $context = []): Money;

    /**
     * Get breakdown of billing calculation
     */
    public function getCalculationBreakdown(Contract $contract, array $context = []): array;

    /**
     * Validate contract data for billing calculation
     */
    public function validateContractData(Contract $contract): array;

    /**
     * Get supported pricing models
     */
    public function getSupportedModels(): array;

    /**
     * Get required contract fields for calculation
     */
    public function getRequiredFields(): array;

    /**
     * Preview calculation without saving
     */
    public function previewCalculation(array $contractData, array $context = []): array;

    /**
     * Get calculation parameters for UI
     */
    public function getCalculationParameters(): array;

    /**
     * Apply discounts and adjustments
     */
    public function applyAdjustments(Money $baseAmount, array $adjustments, Contract $contract): Money;

    /**
     * Calculate taxes if applicable
     */
    public function calculateTaxes(Money $amount, Contract $contract, array $context = []): Money;

    /**
     * Get billing frequency options
     */
    public function getBillingFrequencies(): array;

    /**
     * Check if calculator supports automatic billing
     */
    public function supportsAutomaticBilling(): bool;

    /**
     * Get next billing date
     */
    public function getNextBillingDate(Contract $contract): ?\DateTime;
}
