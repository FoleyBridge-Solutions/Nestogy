<?php

namespace App\Domains\Contract\Services\Generation;

use App\Domains\Contract\Models\Contract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ContractRegenerator implements ContractGenerationStrategy
{
    protected ContractDocumentBuilder $documentBuilder;

    public function __construct(?ContractDocumentBuilder $documentBuilder = null)
    {
        $this->documentBuilder = $documentBuilder ?: new ContractDocumentBuilder();
    }

    public function generate(array $data): Contract
    {
        return DB::transaction(function () use ($data) {
            $contract = $data['contract'];
            $changes = $data['changes'] ?? [];

            Log::info('Regenerating contract', [
                'contract_id' => $contract->id,
                'user_id' => Auth::id(),
            ]);

            $this->applyChanges($contract, $changes);

            if ($changes['regenerate_document'] ?? true) {
                $this->documentBuilder->generate($contract);
            }

            Log::info('Contract regenerated', [
                'contract_id' => $contract->id,
            ]);

            return $contract->fresh();
        });
    }

    protected function applyChanges(Contract $contract, array $changes): void
    {
        $updatable = [
            'title',
            'description',
            'start_date',
            'end_date',
            'value',
            'terms',
            'conditions',
            'status',
        ];

        $updateData = array_intersect_key($changes, array_flip($updatable));

        if (!empty($updateData)) {
            $contract->update($updateData);
            Log::info('Contract updated with changes', [
                'contract_id' => $contract->id,
                'changes' => array_keys($updateData),
            ]);
        }
    }
}
