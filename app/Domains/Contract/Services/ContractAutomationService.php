<?php

namespace App\Domains\Contract\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractAssetAssignment;
use App\Domains\Contract\Models\ContractBillingCalculation;
use App\Domains\Contract\Models\ContractContactAssignment;
use App\Models\Asset;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ContractAutomationService
 *
 * Handles automated behaviors for programmable contracts including:
 * - Auto-assignment of assets and contacts
 * - Automated billing calculations
 * - Invoice generation
 * - Contract lifecycle automation
 */
class ContractAutomationService
{
    /**
     * Process automation for new asset
     */
    public function processNewAsset(Asset $asset)
    {
        Log::info('Processing new asset for contract automation', [
            'asset_id' => $asset->id,
            'client_id' => $asset->client_id,
        ]);

        // Find active contracts for this client with auto-assignment enabled
        $contracts = Contract::where('client_id', $asset->client_id)
            ->where('status', Contract::STATUS_ACTIVE)
            ->whereHas('template', function ($query) {
                $query->whereJsonContains('automation_settings->auto_assign_new_assets', true);
            })
            ->with('template')
            ->get();

        foreach ($contracts as $contract) {
            $this->autoAssignAsset($contract, $asset);
        }
    }

    /**
     * Process automation for new contact
     */
    public function processNewContact(Contact $contact)
    {
        Log::info('Processing new contact for contract automation', [
            'contact_id' => $contact->id,
            'client_id' => $contact->client_id,
        ]);

        // Find active contracts for this client with auto-assignment enabled
        $contracts = Contract::where('client_id', $contact->client_id)
            ->where('status', Contract::STATUS_ACTIVE)
            ->whereHas('template', function ($query) {
                $query->whereJsonContains('automation_settings->auto_assign_new_contacts', true);
            })
            ->with('template')
            ->get();

        foreach ($contracts as $contract) {
            $this->autoAssignContact($contract, $contact);
        }
    }

    /**
     * Auto-assign asset to contract
     */
    protected function autoAssignAsset(Contract $contract, Asset $asset)
    {
        // Check if asset is already assigned
        $existingAssignment = ContractAssetAssignment::where('contract_id', $contract->id)
            ->where('asset_id', $asset->id)
            ->first();

        if ($existingAssignment) {
            return;
        }

        // Get billing rate for this asset type
        $rate = $this->getAssetBillingRate($contract, $asset);

        // Create assignment
        ContractAssetAssignment::create([
            'contract_id' => $contract->id,
            'asset_id' => $asset->id,
            'assigned_date' => now(),
            'monthly_rate' => $rate,
            'status' => 'active',
            'assigned_by' => 'system', // System auto-assignment
        ]);

        Log::info('Auto-assigned asset to contract', [
            'contract_id' => $contract->id,
            'asset_id' => $asset->id,
            'monthly_rate' => $rate,
        ]);

        // Trigger billing recalculation
        $this->triggerBillingRecalculation($contract);
    }

    /**
     * Auto-assign contact to contract
     */
    protected function autoAssignContact(Contract $contract, Contact $contact)
    {
        // Check if contact is already assigned
        $existingAssignment = ContractContactAssignment::where('contract_id', $contract->id)
            ->where('contact_id', $contact->id)
            ->first();

        if ($existingAssignment) {
            return;
        }

        // Get default access tier and rate
        $defaultTier = $this->getDefaultContactTier($contract);
        if (! $defaultTier) {
            return;
        }

        // Create assignment
        ContractContactAssignment::create([
            'contract_id' => $contract->id,
            'contact_id' => $contact->id,
            'assigned_date' => now(),
            'access_tier' => $defaultTier['name'],
            'monthly_rate' => $defaultTier['rate'],
            'status' => 'active',
            'assigned_by' => 'system', // System auto-assignment
        ]);

        Log::info('Auto-assigned contact to contract', [
            'contract_id' => $contract->id,
            'contact_id' => $contact->id,
            'access_tier' => $defaultTier['name'],
            'monthly_rate' => $defaultTier['rate'],
        ]);

        // Trigger billing recalculation
        $this->triggerBillingRecalculation($contract);
    }

    /**
     * Get billing rate for asset based on contract template rules
     */
    protected function getAssetBillingRate(Contract $contract, Asset $asset)
    {
        if (! $contract->template || ! $contract->template->asset_billing_rules) {
            return 0;
        }

        $rules = $contract->template->asset_billing_rules;
        $assetType = $asset->asset_type;

        // Look for specific rate for this asset type
        if (isset($rules['rates'][$assetType])) {
            return (float) $rules['rates'][$assetType];
        }

        // Fall back to default rate
        return (float) ($rules['default_rate'] ?? 0);
    }

    /**
     * Get default contact tier for auto-assignment
     */
    protected function getDefaultContactTier(Contract $contract)
    {
        if (! $contract->template || ! $contract->template->contact_billing_rules) {
            return null;
        }

        $rules = $contract->template->contact_billing_rules;
        $accessTiers = $rules['access_tiers'] ?? [];

        // Find the tier marked as default, or use the first one
        foreach ($accessTiers as $tier) {
            if (isset($tier['is_default']) && $tier['is_default']) {
                return [
                    'name' => $tier['name'],
                    'rate' => (float) ($tier['monthly_rate'] ?? 0),
                ];
            }
        }

        // If no default tier, use the first one
        if (! empty($accessTiers)) {
            $firstTier = reset($accessTiers);

            return [
                'name' => $firstTier['name'],
                'rate' => (float) ($firstTier['monthly_rate'] ?? 0),
            ];
        }

        return null;
    }

    /**
     * Trigger billing recalculation for contract
     */
    public function triggerBillingRecalculation(Contract $contract)
    {
        if (! $this->shouldAutoCalculateBilling($contract)) {
            return;
        }

        try {
            $this->calculateBillingForPeriod($contract, now()->format('Y-m'));
        } catch (\Exception $e) {
            Log::error('Failed to recalculate billing for contract', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if contract should auto-calculate billing
     */
    protected function shouldAutoCalculateBilling(Contract $contract)
    {
        if (! $contract->template || ! $contract->template->automation_settings) {
            return false;
        }

        $settings = $contract->template->automation_settings;

        return $settings['auto_generate_invoices'] ?? false;
    }

    /**
     * Calculate billing for specific period
     */
    public function calculateBillingForPeriod(Contract $contract, string $billingPeriod)
    {
        DB::beginTransaction();

        try {
            // Check if calculation already exists for this period
            $existingCalculation = ContractBillingCalculation::where('contract_id', $contract->id)
                ->where('billing_period', $billingPeriod)
                ->first();

            if ($existingCalculation && $existingCalculation->status !== 'draft') {
                // Don't recalculate finalized periods
                DB::rollBack();

                return $existingCalculation;
            }

            $assetBilling = 0;
            $contactBilling = 0;

            // Calculate asset billing
            if (in_array($contract->billing_model, ['per_asset', 'hybrid'])) {
                $assetBilling = $this->calculateAssetBillingForPeriod($contract, $billingPeriod);
            }

            // Calculate contact billing
            if (in_array($contract->billing_model, ['per_contact', 'hybrid'])) {
                $contactBilling = $this->calculateContactBillingForPeriod($contract, $billingPeriod);
            }

            $totalAmount = $assetBilling + $contactBilling;

            // Create or update calculation
            $calculation = ContractBillingCalculation::updateOrCreate(
                [
                    'contract_id' => $contract->id,
                    'billing_period' => $billingPeriod,
                ],
                [
                    'asset_billing_amount' => $assetBilling,
                    'contact_billing_amount' => $contactBilling,
                    'total_amount' => $totalAmount,
                    'calculated_at' => now(),
                    'status' => 'calculated',
                ]
            );

            DB::commit();

            Log::info('Billing calculated for contract period', [
                'contract_id' => $contract->id,
                'billing_period' => $billingPeriod,
                'asset_billing' => $assetBilling,
                'contact_billing' => $contactBilling,
                'total_amount' => $totalAmount,
            ]);

            return $calculation;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate asset billing for period
     */
    protected function calculateAssetBillingForPeriod(Contract $contract, string $billingPeriod)
    {
        $assignments = ContractAssetAssignment::where('contract_id', $contract->id)
            ->where('status', 'active')
            ->with('asset')
            ->get();

        $total = 0;
        foreach ($assignments as $assignment) {
            $total += $assignment->monthly_rate ?? 0;
        }

        return $total;
    }

    /**
     * Calculate contact billing for period
     */
    protected function calculateContactBillingForPeriod(Contract $contract, string $billingPeriod)
    {
        $assignments = ContractContactAssignment::where('contract_id', $contract->id)
            ->where('status', 'active')
            ->with('contact')
            ->get();

        $total = 0;
        foreach ($assignments as $assignment) {
            $total += $assignment->monthly_rate ?? 0;
        }

        return $total;
    }

    /**
     * Process monthly billing automation for all contracts
     */
    public function processMonthlyBilling()
    {
        $currentPeriod = now()->format('Y-m');

        Log::info('Starting monthly billing automation', [
            'period' => $currentPeriod,
        ]);

        // Get all active contracts with automated billing
        $contracts = Contract::where('status', Contract::STATUS_ACTIVE)
            ->whereHas('template', function ($query) {
                $query->whereJsonContains('automation_settings->auto_generate_invoices', true);
            })
            ->with('template')
            ->get();

        $processedCount = 0;
        $errorCount = 0;

        foreach ($contracts as $contract) {
            try {
                $this->calculateBillingForPeriod($contract, $currentPeriod);
                $processedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to process monthly billing for contract', [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Monthly billing automation completed', [
            'period' => $currentPeriod,
            'processed' => $processedCount,
            'errors' => $errorCount,
        ]);

        return [
            'processed' => $processedCount,
            'errors' => $errorCount,
            'period' => $currentPeriod,
        ];
    }

    /**
     * Execute automation workflows for contract
     */
    public function executeAutomationWorkflows(Contract $contract, string $trigger)
    {
        if (! $contract->template || ! $contract->template->automation_settings) {
            return;
        }

        $settings = $contract->template->automation_settings;

        switch ($trigger) {
            case 'contract_activated':
                $this->handleContractActivatedWorkflow($contract, $settings);
                break;

            case 'billing_calculated':
                $this->handleBillingCalculatedWorkflow($contract, $settings);
                break;

            case 'asset_added':
                $this->handleAssetAddedWorkflow($contract, $settings);
                break;

            case 'contact_added':
                $this->handleContactAddedWorkflow($contract, $settings);
                break;
        }
    }

    /**
     * Handle contract activated workflow
     */
    protected function handleContractActivatedWorkflow(Contract $contract, array $settings)
    {
        // Auto-assign existing assets if enabled
        if ($settings['auto_assign_new_assets'] ?? false) {
            $assets = Asset::where('client_id', $contract->client_id)->get();
            foreach ($assets as $asset) {
                $this->autoAssignAsset($contract, $asset);
            }
        }

        // Auto-assign existing contacts if enabled
        if ($settings['auto_assign_new_contacts'] ?? false) {
            $contacts = Contact::where('client_id', $contract->client_id)->get();
            foreach ($contacts as $contact) {
                $this->autoAssignContact($contract, $contact);
            }
        }
    }

    /**
     * Handle billing calculated workflow
     */
    protected function handleBillingCalculatedWorkflow(Contract $contract, array $settings)
    {
        // Auto-generate invoices if enabled
        if ($settings['auto_generate_invoices'] ?? false) {
            // This would integrate with your invoicing system
            Log::info('Auto-invoice generation triggered', [
                'contract_id' => $contract->id,
            ]);
        }
    }

    /**
     * Handle asset added workflow
     */
    protected function handleAssetAddedWorkflow(Contract $contract, array $settings)
    {
        // Additional automation when assets are added
        Log::info('Asset added workflow triggered', [
            'contract_id' => $contract->id,
        ]);
    }

    /**
     * Handle contact added workflow
     */
    protected function handleContactAddedWorkflow(Contract $contract, array $settings)
    {
        // Additional automation when contacts are added
        Log::info('Contact added workflow triggered', [
            'contract_id' => $contract->id,
        ]);
    }
}
