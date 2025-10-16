<?php

namespace App\Domains\Contract\Services\Generation;

use App\Domains\Contract\Models\Contract;
use App\Domains\Financial\Models\Quote;
use Illuminate\Support\Facades\Log;

class ContractComplianceInitializer
{
    public function initialize(Contract $contract, ?Quote $quote = null): void
    {
        try {
            Log::info('Initializing contract compliance tracking', ['contract_id' => $contract->id]);

            if ($quote) {
                $this->syncComplianceRequirements($contract, $quote);
            }

            $this->initializeAuditTrail($contract);

            Log::info('Contract compliance initialized', ['contract_id' => $contract->id]);
        } catch (\Exception $e) {
            Log::error('Failed to initialize contract compliance', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function syncComplianceRequirements(Contract $contract, Quote $quote): void
    {
        if ($quote->compliance_requirements) {
            foreach ($quote->compliance_requirements as $requirement) {
                $contract->complianceRequirements()->create([
                    'requirement' => $requirement,
                    'status' => 'pending',
                ]);
            }
        }
    }

    protected function initializeAuditTrail(Contract $contract): void
    {
        $contract->auditLog()->create([
            'action' => 'created',
            'user_id' => auth()->id(),
            'details' => [
                'status' => $contract->status,
                'template_id' => $contract->template_id,
            ],
        ]);
    }
}
